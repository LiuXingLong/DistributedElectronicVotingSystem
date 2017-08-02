<?php
namespace Common\Model;

class VoteproModel extends BaseModel
{
	protected function init() {
		$this->DB = M($this->table , 'votepro_', 'DB_TPB_'.$this->databases );
	}
}