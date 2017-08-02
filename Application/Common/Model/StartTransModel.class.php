<?php
namespace Common\Model;

class StartTransModel extends BaseModel
{
	protected function init() {
		$this->Trans = M('' , '', 'DB_TPB_'.$this->databases );
	}
}