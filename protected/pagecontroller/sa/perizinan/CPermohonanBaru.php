<?php
prado::using ('Application.MainPageSA');
class CPermohonanBaru extends MainPageSA {
	public function onLoad($param) {		
		parent::onLoad($param);		
        $this->showPerizinan=true;
        $this->showPermohonanBaru=true;        
		if (!$this->IsPostBack&&!$this->IsCallBack) {
            
		}
	}
}
		