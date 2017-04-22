public $assign_rights=array();

	public function __construct(Route $route){
		$aname=explode('@',$route->getActionName());
		parent::__construct();
		$final_show_data=$this->final_show_data;
		if(!empty($final_show_data)){
			asort($final_show_data);
				foreach ($final_show_data as $key=>$value){
					if($key=='Business'){
						if(!empty($value)){
						asort($value);
						foreach($value as $ky=>$val){
							if($val['status']==1) {
								if($val['module_name']=='Business'){
									//print_r($val);
										//$this->assign_rights['rights']='admin/business';
									if($val['submodule_name']==1 && $aname[1]='addBusinessUserView'){
											$add=$this->assign_rights['rights']='';
										}
									if($val['submodule_name']==2 && $aname[1]='editBusinessAdminDetailsview'){
											$this->assign_rights['rights']='';
									}
									if($val['submodule_name']==3 && $aname[1]='getBusinessdminDetails'){
										$this->assign_rights['rights']='';
									}
									else{
										
									}
									
								}
							}
						}	
					}
				}
			}
		}
	}
