<?php
namespace Common\Model;

class VoteinfoModel extends BaseModel
{
	protected function init() {
		$this->DB = M($this->table , 'voteinfo_', 'DB_TPB_'.$this->databases );
	}
}