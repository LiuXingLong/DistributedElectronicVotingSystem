<?php
namespace Common\Model;

class VotetprModel extends BaseModel
{
	protected function init() {
		$this->DB = M($this->table , 'votetpr_', 'DB_TPB_'.$this->databases );
	}
}