<?php
prado::using ('Application.MainPageAD');
class CPengajuanSIPI extends MainPageAD {          
    public function onLoad($param) {		
		parent::onLoad($param);		        
        $this->showDaftarPengajuan=true; 
        $this->showPengajuanSIPI=true;                 
        $this->createObj('Pemohon');
		if (!$this->IsPostBack&&!$this->IsCallBack) {
            if (isset($_SESSION['currentPagePengajuanSIPI']['datapengajuan']['recnobup'])) {
                $this->dataPengajuan=$_SESSION['currentPagePengajuanSIPI']['datapengajuan'];
                $this->idProcess='view';
                $this->MultiView->ActiveViewIndex=$this->dataPengajuan['currentview'];
            }else {                
                if (!isset($_SESSION['currentPagePengajuanSIPI'])||$_SESSION['currentPagePengajuanSIPI']['page_name']!='ad.perizinan.PengajuanSIPI') {
                    $_SESSION['currentPagePengajuanSIPI']=array('page_name'=>'ad.perizinan.PengajuanSIPI','page_num'=>0,'search'=>false,'iduptd'=>$this->Pengguna->getDataUser('iduptd'),'jenispengajuan'=>'none','datapengajuan'=>array());	                
                }       
                $this->cmbJenisPengajuan->Text=$_SESSION['currentPagePengajuanSIPI']['jenispengajuan'];
                $this->labelDaftarPengajuan->Text=$_SESSION['currentPagePengajuanSIPI']['jenispengajuan']=='none'?'':strtoupper($_SESSION['currentPagePengajuanSIPI']['jenispengajuan']);
                $this->populateData();
            }
		}
	}
    public function renderCallback ($sender,$param) {
		$this->RepeaterS->render($param->NewWriter);	
	}	
	public function Page_Changed ($sender,$param) {
		$_SESSION['currentPagePengajuanSIPI']['page_num']=$param->NewPageIndex;
		$this->populateData($_SESSION['currentPagePengajuanSIPI']['search']);
	}
    public function changeStatusPengajuan($sender,$param) {
        $_SESSION['currentPagePengajuanSIPI']['jenispengajuan']=$this->cmbJenisPengajuan->Text;    
        $this->labelDaftarPengajuan->Text=$_SESSION['currentPagePengajuanSIPI']['jenispengajuan']=='none'?'':strtoupper($_SESSION['currentPagePengajuanSIPI']['jenispengajuan']);
        $this->populateData();
    }
    protected function populateData ($search=false) {
        $iduptd=$_SESSION['currentPagePengajuanSIPI']['iduptd'];
        $str_stastuspermohonan=$_SESSION['currentPagePengajuanSIPI']['jenispengajuan']=='none'?'':"AND JnsDtSIUP='{$_SESSION['currentPagePengajuanSIPI']['jenispengajuan']}'";
        $str = "SELECT s.RecNoSiup,bup.RecNoBup,JnsDtSIUP,NoRegSiup,NmPem,NoSrtSiup,TglSrtSiup,StatusBup,bup.date_added FROM siup s JOIN bup ON (bup.RecNoSiup=s.RecNoSiup) LEFT JOIN siup_data_pemohon sdp ON (sdp.RecNoSiup=s.RecNoSiup) WHERE s.RecNoIzin=1 AND s.iduptd=$iduptd $str_stastuspermohonan";
        if ($search) {
            
        }else{
            $jumlah_baris=$this->DB->getCountRowsOfTable ("siup WHERE RecNoIzin=1 AND iduptd=$iduptd $str_stastuspermohonan",'RecNoPem');			
        }
        $this->RepeaterS->CurrentPageIndex=$_SESSION['currentPagePengajuanSIPI']['page_num'];		
		$this->RepeaterS->VirtualItemCount=$jumlah_baris;
		$currentPage=$this->RepeaterS->CurrentPageIndex;
		$offset=$currentPage*$this->RepeaterS->PageSize;		
		$itemcount=$this->RepeaterS->VirtualItemCount;
		$limit=$this->RepeaterS->PageSize;
		if (($offset+$limit)>$itemcount) {
			$limit=$itemcount-$offset;
		}
		if ($limit <= 0) {$offset=0;$limit=10;$_SESSION['currentPagePengajuanSIPI']['page_num']=0;}
        $str = "$str ORDER BY date_added DESC LIMIT $offset,$limit";        
        $this->DB->setFieldTable(array('RecNoSiup','RecNoBup','NmPem','JnsDtSIUP','NoRegSiup','NoSrtSiup','TglSrtSiup','StatusBup','date_added'));
		$r=$this->DB->getRecord($str,$offset+1);        
		$this->RepeaterS->DataSource=$r;
		$this->RepeaterS->dataBind();      		
    }
    public function deleteRecord ($sender,$param) {
        $recnosiup=$sender->CommandParameter;
        $this->DB->deleteRecord("siup WHERE RecNoSiup='$recnosiup'");        
        $this->redirect('perizinan.PengajuanSIPI',true);					        
    }
    public function viewDetailPengajuan ($sender,$param) {
        $recnobup=$this->getDataKeyField($sender,$this->RepeaterS);
        $recnosiup=$sender->CommandParameter;  
        $this->Pemohon->setRecNoPem($recnosiup,true,1);        
        $data=$this->Pemohon->DataPemohon;        
        $data['recnobup']=$recnobup;
        $data['recnosiup']=$recnosiup;
        $data['currentview']=0;                       
        $_SESSION['currentPagePengajuanSIPI']['datapengajuan']=$data;
        $this->redirect('perizinan.PengajuanSIPI',true);
    }    
    public function viewChanged($sender,$param) {
        $this->idProcess='view'; 
        $activeviewindex=$this->MultiView->ActiveViewIndex;
        $_SESSION['currentPagePengajuanSIPI']['datapengajuan']['currentview']=$activeviewindex;
    }
    public function closeDetailPengajuan ($sender,$param) {
        $_SESSION['currentPagePengajuanSIPI']['datapengajuan']=array();
        $this->redirect('perizinan.PengajuanSIPI',true);
    }
    public function verifikasiData ($sender,$param) {
        $this->dataPengajuan=$_SESSION['currentPagePengajuanSIPI']['datapengajuan'];
        $recnobup=$this->dataPengajuan['recnobup'];
        $recnosiup=$this->dataPengajuan['recnosiup'];
        $this->DB->query('BEGIN');        
        $str = "UPDATE bup SET StatusBup='verified',date_modified=NOW() WHERE RecNoBup=$recnobup";
        if ($this->DB->updateRecord($str)) {
            $str = "UPDATE siup SET StatusSiup='verified',date_modified=NOW() WHERE RecNoSiup=$recnosiup";
            $this->DB->updateRecord($str);
            $this->DB->query('COMMIT');
        }else {
            $this->DB->query('ROLLBACK');
        }
        $this->redirect('perizinan.PengajuanSIPI', true);
    }
    public function approvalData ($sender,$param) {
        $this->dataPengajuan=$_SESSION['currentPagePengajuanSIPI']['datapengajuan'];
        $recnobup=$this->dataPengajuan['recnobup'];
        $recnosiup=$this->dataPengajuan['recnosiup'];
        $this->DB->query('BEGIN');
        $str = "UPDATE bup SET StatusBup='approved',date_modified=NOW() WHERE RecNoBup=$recnobup";
        if ($this->DB->updateRecord($str)) {
            $str = "UPDATE siup SET StatusSiup='approved',date_modified=NOW() WHERE RecNoSiup=$recnosiup";
            $this->DB->updateRecord($str);
            $this->DB->query('COMMIT');
        }else {
            $this->DB->query('ROLLBACK');
        }
        $this->redirect('perizinan.PengajuanSIPI', true);
    }
    public function printOut ($sender,$param) {        
        $processprintout=$sender->CommandParameter;
        $dataReport=$_SESSION['currentPagePengajuanSIPI']['datapengajuan'];        
        $dataReport['outputmode']='pdf';        
        $dataReport['linkoutput']=$this->linkOutput;                
        switch ($processprintout) {
            case 'pemeriksaankapal':  
                $label='Form Pemeriksaan Fisik Kapal';
                $this->Pemohon->setDataReport($dataReport);
                $this->Pemohon->printFormPemeriksaanFisikKapal();
            break;
            case 'suratpengantar':                
                $label='Surat Pengantar dari KA. UPT';
                $this->Pemohon->setDataReport($dataReport);
                $this->Pemohon->printSuratPengantarUPT();
            break;
        }        
        $this->lblPrintout->Text = $label;
        $this->modalPrintOut->show();
    }
    
}
		