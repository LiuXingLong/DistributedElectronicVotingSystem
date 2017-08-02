<?php
namespace Common\Model;

class VotehxrModel extends BaseModel
{
	protected function init() {
		$this->DB = M($this->table , 'votehxr_', 'DB_TPB_'.$this->databases );
	}
}