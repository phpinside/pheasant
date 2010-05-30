<?php

namespace pheasant\tests\transaction;
use \pheasant\database\mysqli\Transaction;

require_once('autorun.php');
require_once(__DIR__.'/base.php');

class TransactionTestCase extends \pheasant\tests\MysqlTestCase
{
	public function testBasicSuccessfulTransaction()
	{
		$connection = new \MockConnection();
		$connection->expectAt(0,'execute',array('begin'));
		$connection->expectAt(1,'execute',array('commit'));

		$transaction = new Transaction($connection);
		$transaction->callback(function(){
			return 'blargh';
		});

		$transaction->execute();
		$this->assertEqual(count($transaction->results), 1);
		$this->assertEqual($transaction->results[0], 'blargh');
	}

	public function testExceptionsCauseRollback()
	{
		$connection = new \MockConnection();
		$connection->expectAt(0,'execute',array('begin'));
		$connection->expectAt(1,'execute',array('rollback'));

		$transaction = new Transaction($connection);
		$transaction->callback(function(){
			throw new \Exception('Eeeek!');
		});

		try
		{
			$transaction->execute();
			$this->fail("exception should have been thrown");
		}
		catch(\Exception $e) {}
	}

	public function testCallbacksWithConnectionCalls()
	{
		$sql = "SELECT * FROM table";
		$connection = new \MockConnection();
		$connection->expectAt(0,'execute',array('begin'));
		$connection->expectAt(1,'execute',array($sql));
		$connection->expectAt(2,'execute',array('commit'));

		$transaction = new Transaction($connection);
		$transaction->callback(function() use($connection, $sql) {
			$connection->execute($sql);
		});

		$transaction->execute();
	}

	public function testCallbacksWithParams()
	{
		$connection = new \MockConnection();
		$connection->expectAt(0,'execute',array('begin'));
		$connection->expectAt(2,'execute',array('commit'));

		$transaction = new Transaction($connection);
		$transaction->callback(function($param) {
			return $param;
		}, 'blargh');

		$transaction->execute();
		$this->assertEqual(count($transaction->results), 1);
		$this->assertEqual($transaction->results[0], 'blargh');
	}
}
