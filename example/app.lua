local gearman = require "resty.gearman"
local cjson = require "cjson"
local gm = gearman:new()
local pairs = pairs
local ngx = ngx

local TIMEOUT = 100000 -- 1 sec


module(...)

_VERSION = '0.0.1'

function process(self, hostname, port, channel)
    _set_timeout()
    _connect(hostname, port)
    local ok, err = _request(channel)
    if not ok then
        _error("failed to submit job: ", err)
        return
    else
        _parser_response(ok)
    end

end

function _connect(hostname, port)
    local ok, err = gm:connect(hostname, port)
    if not ok then
        _error("failed to connect: ", err)
        return
    end
end

function _set_timeout()
    gm:set_timeout(TIMEOUT)
end

function _request(channel)
    return gm:submit_job(channel, _create_request())
end

function _parser_response(json)
    response = cjson.decode(json)
    ngx.status = response.code
    for name, header in pairs(response.headers) do
        ngx.header[name] = header
    end

    ngx.say(response.body)
    return
end

function _create_request()
    ngx.req.read_body()
    return cjson.encode({
        uri = ngx.var.uri,
        method = ngx.req.get_method(),
        headers = ngx.req.get_headers(),
        post = ngx.req.get_post_args(),
        get = ngx.req.get_uri_args(),
        body = ngx.req.get_body_data(),
        remote = {
            addr = ngx.var.remote_addr,
            user = ngx.var.remote_user,
            port = ngx.var.remote_port
        },
        server = {
            addr = ngx.var.server_addr,
            name = ngx.var.server_name,
            port = ngx.var.server_port,
            proto = ngx.var.server_protocol
        }
    })
end

function _set_keepalive()
    local ok, err = gm:set_keepalive(0)
    if not ok then
        _error("failed to set keepalive: ", err)
        return
    end
end

function _error(msg, err)
    ngx.say(msg, error)
end
