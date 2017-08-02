<?php
namespace Common\Model;

class TransactiontprModel extends BaseModel
{
	protected function init() {
		$this->DB = M($this->table , 'transaction_tpr_', 'DB_TPB_'.$this->databases );
	}
}