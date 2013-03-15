<?php
namespace Skeetr\Tests;
use Skeetr\Client\Journal;

class JournalTest extends TestCase {
    public function testAddSuccess() {
        $journal = new Journal();
        $journal->addSuccess(5);

        $this->assertSame(1, $journal->getWorks());
        $this->assertSame(5, $journal->getAvgTime());

        $journal->addSuccess(2);
        $this->assertSame(3.5, $journal->getAvgTime());
    }

    public function testAddError() {
        $journal = new Journal();
        $journal->addError('error text');

        $this->assertSame(1, $journal->getErrors());
        $this->assertSame('error text', $journal->getLastError());

        $journal->addError('another text');
        $this->assertSame(2, $journal->getErrors());
        $this->assertSame('another text', $journal->getLastError());
    }

    public function testAddTimeout() {
        $journal = new Journal();
        $journal->addTimeout();

        $this->assertSame(1, $journal->getTimeouts());
    }

    public function testAddLostConnection() {
        $journal = new Journal();
        $journal->addLostConnection(1);

        $this->assertSame(1, $journal->getLostConnection());

        $journal->addLostConnection(2);
        $this->assertSame(3, $journal->getLostConnection());
    }

    public function testAddIdle() {
        $journal = new Journal();
        $journal->addIdle(1);

        $this->assertSame(1, $journal->getIdle());

        $journal->addIdle(2);
        $this->assertSame(3, $journal->getIdle());
    }

    public function testGetData() {
        $journal = new Journal();
        $this->assertTrue(is_array($journal->getData()));
    }
    
    public function testGetJson() {
        $journal = new Journal();
        $this->assertTrue(is_string($journal->getJson()));
    }
}