<?php
prado::using ('Application.MainPageSA');
class CPemohon extends MainPageSA {
	public function onLoad($param) {		
		parent::onLoad($param);		
        $this->showPerizinan=true;
        $this->showPemohon=true;        
		if (!$this->IsPostBack&&!$this->IsCallBack) {
            if (!isset($_SESSION['currentPagePemohon'])||$_SESSION['currentPagePemohon']['page_name']!='sa.dmaster.Pemohon') {
                $_SESSION['currentPagePemohon']=array('page_name'=>'sa.dmaster.Pemohon','page_num'=>0,'search'=>false);	                
            }        
            $_SESSION['currentPagePemohon']['search']=false;
            $this->populateData();
		}
	}    
    public function renderCallback ($sender,$param) {
		$this->RepeaterS->render($param->NewWriter);	
	}	
	public function Page_Changed ($sender,$param) {
		$_SESSION['currentPagePemohon']['page_num']=$param->NewPageIndex;
		$this->populateData($_SESSION['currentPagePemohon']['search']);
	} 
    public function filterRecord ($sender,$param) {
		$_SESSION['currentPagePemohon']['search']=true;
        $this->populateData($_SESSION['currentPagePemohon']['search']);
	}
    private function populateData ($search=false) {
        $str = "SELECT RecNoPem,NmPem,KtpPem,AlmtPem,TelpPem,Foto,DateAdded FROM pemohon";
        if ($search) {
            $txtsearch=$this->txtKriteria->Text;
            switch ($this->cmbKriteria->Text) {
                case 'kode' :
                    $cluasa="WHERE RecNoPem='$txtsearch'";
                    $jumlah_baris=$this->DB->getCountRowsOfTable ("pemohon $cluasa",'RecNoPem');
                    $str = "$str $cluasa";
                break;
                case 'nama_pemohon' :
                    $cluasa="WHERE nama_updt LIKE '%$txtsearch%'";
                    $jumlah_baris=$this->DB->getCountRowsOfTable ("pemohon $cluasa",'RecNoPem');
                    $str = "$str $cluasa";
                break;
            }
        }else {
            $jumlah_baris=$this->DB->getCountRowsOfTable ('pemohon','RecNoPem');			
        }
        $this->RepeaterS->CurrentPageIndex=$_SESSION['currentPagePemohon']['page_num'];		
		$this->RepeaterS->VirtualItemCount=$jumlah_baris;
		$currentPage=$this->RepeaterS->CurrentPageIndex;
		$offset=$currentPage*$this->RepeaterS->PageSize;		
		$itemcount=$this->RepeaterS->VirtualItemCount;
		$limit=$this->RepeaterS->PageSize;
		if (($offset+$limit)>$itemcount) {
			$limit=$itemcount-$offset;
		}
		if ($limit <= 0) {$offset=0;$limit=10;$_SESSION['currentPagePemohon']['page_num']=0;}
        $str = "$str LIMIT $offset,$limit";        
		$this->DB->setFieldTable(array('RecNoPem','NmPem','KtpPem','AlmtPem','TelpPem','Foto','DateAdded'));
		$r=$this->DB->getRecord($str,$offset+1);        
		$this->RepeaterS->DataSource=$r;
		$this->RepeaterS->dataBind();      		
    }
    public function checkId ($sender,$param) {
		$this->idProcess=$sender->getId()=='addKodeUPDT'?'add':'edit';
        $idpemohon=$param->Value;		
        if ($idpemohon != '') {
            try {   
                if ($this->hiddenidpemohon->Value!=$idpemohon) {                    
                    if ($this->DB->checkRecordIsExist('RecNoPem','pemohon',$idpemohon)) {                                
                        throw new Exception ("ID Pemohon ($idpemohon) sudah tidak tersedia silahkan ganti dengan yang lain.");		
                    }                               
                }                
            }catch (Exception $e) {
                $param->IsValid=false;
                $sender->ErrorMessage=$e->getMessage();
            }	
        }	
    }
    public function checkTypeFile ($sender,$param) {
        $this->idProcess=$sender->getId()=='addFileFoto'?'add':'edit';        
        if ($this->FileFoto->FileType!='image/png' && $this->FileFoto->FileType!='image/jpeg')            
            $param->IsValid=false;
    }
    public function processNextButton($sender,$param) {
        $this->idProcess='add';                    
		if ($param->CurrentStepIndex ==0) {                   
            if ($this->rdAddStatusPemohonPerorangan->Checked) {
                $this->newpemohonwizard->ActiveStepIndex=2; 
            }
		}
	}
    public function addNewPemohonCompleted ($sender,$param) {
        $this->idProcess='add'; 
        $RecNoPem=$this->DB->getMaxOfRecord('RecNoPem','pemohon')+1;
        $name=$this->FileFoto->FileName;
        $part=$this->setup->cleanFileNameString($name);                
        $path=$this->setup->getSettingValue('dir_temp')."/$RecNoPem-$part";                
        $this->path_temp_userimages->Value=$path;
        $path=$this->setup->getSettingValue('dir_userimages')."/$RecNoPem-$part";                
        $this->path_userimages->Value=$path;
        $this->FileFoto->saveAs("./$path");      
    }    
    public function saveData($sender,$param) {		
        if ($this->Page->IsValid) {		                                                                
            $status_pemohon=$this->rdAddStatusPemohonPerorangan->Checked==true?'perorangan':'perusahaan';
            $kode_pemohon=$this->txtAddKodePemohon->Text;
            $nama_pemohon=$this->txtAddNamaPemohon->Text;
            $no_ktp_pemohon=$this->txtAddNoKTP->Text;
            $alamat_pemohon=$this->txtAddAlamatPemohon->Text;
            $notelp_pemohon=$this->txtAddNoTelp->Text;
            $path_temp_userimages=$this->path_temp_userimages->Value;
            $path_userimages=$this->path_userimages->Value;
            
            $str = "INSERT INTO pemohon (RecNoPem,NmPem,KtpPem,AlmtPem,TelpPem,Foto,Status,DateAdded,DateModified) VALUES ('$kode_pemohon','$nama_pemohon','$no_ktp_pemohon','$alamat_pemohon','$notelp_pemohon','$path_userimages','$status_pemohon',NOW(),NOW())";
            $this->DB->query('BEGIN');
            if ($this->DB->insertRecord($str)) {
                if ($status_pemohon=='perusahaan') {
                    $nama_perusahaan=$this->txtAddNamaPerusahaan->Text;
                    $no_akte=$this->txtAddNoAktePerusahaan->Text;
                    $tgl_akte=date('Y-m-d',$this->cmbAddTGLAktePendirian->TimeStamp);
                    $npwp=$this->txtAddNoNPWP->Text;
                    $alamat=$this->txtAddAlamatPerusahaan->Text;
                    $notelepon=$this->txtAddNoTelpPerusahaan->Text;
                    $nofax=$this->txtAddNoFaxPerusahaan->Text;
                    $alamatcabang=$this->txtAddAlamatCabang->Text;
                    
                             
                }
                $this->DB->query('COMMIT');
            }else {
                $this->DB->query('ROLLBACK');
            }          
        }
	}
    public function editRecord ($sender,$param) {		
		$this->idProcess='edit';
		
	}
    public function updateData($sender,$param) {		
        if ($this->Page->IsValid) {		
            
        }
	}
    public function deleteRecord ($sender,$param) {
        $id=$this->getDataKeyField($sender,$this->RepeaterS);
        $this->DB->query('BEGIN');
        if ($this->DB->deleteRecord("pemohon WHERE RecNoPem='$id'")) {                                    
            $this->DB->query('COMMIT');
            $this->redirect('dmaster.Pemohon',true);					
        }else{
            $this->DB->query('ROLLBACK');
        }
    }
}
		