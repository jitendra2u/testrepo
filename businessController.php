<?php
use Illuminate\Routing\Controller;
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use App\Helper\Utils;
use App\Helper\WebServiceResponse;
use Session;
use App\User;
use App\customers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Redirect;
use View;
use DB;
use Storage;
use App\Models\Business;
use App\Models\business_contact_person;


class BusinessController extends Controller {

	public $assign_rights=array();

	public function __construct(Route $route){
		$aname=explode('@',$route->getActionName());
		parent::__construct();
		$final_show_data=$this->final_show_data;
		if(!empty($final_show_data)){
			asort($final_show_data);
			
			//var_dump($final_show_data);
				foreach ($final_show_data as $key=>$value){
					if($key=='Business'){
						if(!empty($value)){
						asort($value);
						foreach($value as $ky=>$val){
							//var_dump($val);
							if($val['status']==true) {
								//print_r($val);die;
								$this->assign_rights[]=$val['submodule_name'];
							// 	if($val['module_name']=='Business'){
							// //		print_r($val);
							// 			//$this->assign_rights['rights']='admin/business';
							// 		if($val['submodule_name']==1 && $aname[1]='addBusinessUserView'){
							// 				$this->assign_rights['rights'][1]='Add';
							// 			}
							// 		else if($val['submodule_name']==2 && $aname[1]='editBusinessAdminDetailsview'){
							// 				$this->assign_rights['rights'][2]='Edit';
							// 		}
							// 		else if($val['submodule_name']==3 && $aname[1]='getBusinessdminDetails'){
							// 				$this->assign_rights['rights'][3]='View';
							// 		}
							// 		else{
										
							// 		}
									
							// 	}
							}
						}	
					}
				}
			} // End Foreach
			//print_r($this->assign_rights);
		}
	}
	


	/*Add Business view form*/
    public function addBusinessUserView(){
    	if(in_array("Add", $this->assign_rights)){
    		$countries = $this->getCountry();
			return view('admin.add_business_user', compact('countries'));
    	}
    		
		else{
			Session::flash('msg','You are not authorized to access this location.');
    		return Redirect::to('admin/business');	
		}
    }

	/*Save business to databases*/
    public function SaveBussinessUser(Request $request)
	{	
        $this->validate($request, [
         'bussiness_name' => 'required|max:100',
         'phone' => 'required|numeric|regex:/[0-9]{10}/',
		 'email' => 'required|email|unique:bussiness',
		 'address' => 'required|max:255',
		 'country' => 'required',
		 'state' => 'required',
		 'city' => 'required',
		 'description' => 'required|max:255',
		 'website' => 'required|regex:/^(https?:\/\/)?([\da-z\.-]+)\.([A-Za-z\.]{2,6})([\/\w \.-]*)*\/?$/',
		 'expirydate' => 'required',
		 'noofdevices' => 'required|numeric',
		 'zipcode'=>'required',
		 'cp_name' => 'required|max:100',
		 'cp_email' => 'required|email',
		 'cp_phone' => 'required|numeric|regex:/[0-9]{10}/',
		 'cp_country' => 'required',
		 'cp_state' => 'required',
		 'cp_city' => 'required',
		 'companylogo' => 'required|max:2048|mimes:jpeg,png,jpg,gif,svg',
		 
        ]); 
       
		//Business information 
		$business = new Business();
        $business->bussiness_name  = $this->rip_tags($request['bussiness_name']);
        $business->phone = $request['phone'];
		$bEmail = strtolower(trim($request['email']));
        $business->email   = $bEmail;
        $business->address= $request['address'];
        $business->countryid   = $request['country'];
        $business->stateid  = $request['state'];
        $business->cityid   = $request['city'];
        $business->description   = $this->rip_tags($request['description']);
		$business->website   = strtolower(trim($request['website']));
		$business->expirydate  = $request['expirydate'];
		$business->noofdevices   = $request['noofdevices'];
		$business->zipcode   = $request['zipcode'];
		$business->status   = true;
		$business->created_by = 1;
        //Business contact person information 
		$contactPersonInfo = new business_contact_person();
		$contactPersonInfo->cp_name = $this->rip_tags($request['cp_name']);
		$contactPersonInfo->cp_email = trim($request['cp_email']);
		$contactPersonInfo->cp_phone = $request['cp_phone'];
		$contactPersonInfo->cp_country = $request['cp_country'];
		$contactPersonInfo->cp_state = $request['cp_state'];
		$contactPersonInfo->cp_city = $request['cp_city'];
		//Customer information
		$customer = new customers();
		$customer->first_name = $request['bussiness_name'];
		$customer->email = $bEmail;
		$customer->mobile = $request['phone'];
		$customer->status = true;
		$customer->created_date = date('Y-m-d h:i:s');
		$customer->address = $request['address'];
		$customer->country_id = $request['country'];
		$customer->state_id = $request['state'];
		$customer->city_id = $request['city'];
		$customer->pincode_id = $request['zipcode'];
		
        $check_user_name=DB::table('users as u')
            ->select('u.user_name')
           ->where('u.user_name', '=', $bEmail)->get();
		   
		$check_business_user=DB::table('bussiness as b')
            ->select('b.email')
           ->where('b.email', '=', $bEmail)->get();
		   
		$check_customer = DB::table('customers as c')
            ->select('c.email')
           ->where('c.email', '=', $bEmail)->get();

        if((count($check_user_name) == 0) && (count($check_business_user) == 0) && (count($check_customer) == 0))
        {
            //upload Company logo
			$image = $request->file('companylogo'); 
			$imageName = time().'.'.$image->getClientOriginalExtension();
			$image->move(
				base_path() . '/asset/company_logo/', $imageName
			);
			$business->companylogo =$imageName;
			if($business->save())
			{
				$bussiness_id = $business->bussiness_id;
				if(!empty($bussiness_id))
				{
					$customer->business_id = $bussiness_id;
					if($customer->save())
					{
						
						$user = new User();
						$user->user_name =$bEmail;
						$randomPass = $this->random_password(8);
						$user->password =bcrypt($randomPass);
						$user->user_type =3;//for business admin
						$user->business_id =$bussiness_id;
						$user->role_id = 3;
						$user->cust_id = $customer->cust_id;
						if($user->save())
						{
							$userEmail = $bEmail;
							$tempPass = $randomPass;
							$Name = $request['bussiness_name'];
							$type='business';
							$urlData = base64_encode($userEmail.'@a@'.$tempPass.'@a@'.$Name.'@b@'.$type);
							$account_create_html ='<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"><html> <head><link href="https://fonts.googleapis.com/css?family=Roboto" rel="stylesheet"><link href="https://fonts.googleapis.com/css?family=Open+Sans" rel="stylesheet"> <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/> <meta name="viewport" content="width=device-width, initial-scale=1.0"> <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"> <meta name="format-detection" content="telephone=no"/> <title></title> <style type="text/css"> /* font roboto */ body *{font-family:\'Roboto\', sans-serif;font-family: \'Open Sans\', sans-serif;}/* RESET STYLES */ body, #bodyTable, #bodyCell, #bodyCell{height:100% !important; margin:0; padding:0; width:100% !important;font-family:Arial, "Lucida Grande", sans-serif;background-color: #fff}table{border-collapse:collapse;}table[id=bodyTable]{width:100%!important;margin:auto;max-width:500px!important;color:#7A7A7A;}img, a img{border:0; outline:none; text-decoration:none;height:auto; line-height:100%;}a{text-decoration:none !important;}h1, h2, h3, h4, h5, h6{color:#5F5F5F; font-family:Helvetica; font-size:20px; line-height:125%; text-align:Left; letter-spacing:normal;margin-top:0;margin-right:0;margin-bottom:10px;margin-left:0;padding-top:0;padding-bottom:0;padding-left:0;padding-right:0;}/* CLIENT-SPECIFIC STYLES */ .ReadMsgBody{width:100%;}.ExternalClass{width:100%;}/* Force Hotmail/Outlook.com to display emails at full width. */ .ExternalClass, .ExternalClass p, .ExternalClass span, .ExternalClass font, .ExternalClass td, .ExternalClass div{line-height:100%;}/* Force Hotmail/Outlook.com to display line heights normally. */ table, td{mso-table-lspace:0pt; mso-table-rspace:0pt;}/* Remove spacing between tables in Outlook 2007 and up. */ #outlook a{padding:0;}/* Force Outlook 2007 and up to provide a "view in browser" message. */ img{-ms-interpolation-mode: bicubic;display:block;outline:none; text-decoration:none;}/* Force IE to smoothly render resized images. */ body, table, td, p, a, li, blockquote{-ms-text-size-adjust:100%; -webkit-text-size-adjust:100%;}/* Prevent Windows- and Webkit-based mobile platforms from changing declared text sizes. */ .ExternalClass td[class="ecxflexibleContainerBox"] h3{padding-top: 10px !important;}/* Force hotmail to push 2-grid sub headers down */ /* /\/\/\/\/\/\/\/\/ TEMPLATE STYLES /\/\/\/\/\/\/\/\/ */ /*==========Page Styles==========*/ h1{display:block;font-size:26px;font-style:normal;font-weight:normal;line-height:100%;}h2{display:block;font-size:20px;font-style:normal;font-weight:normal;line-height:120%;}h3{display:block;font-size:17px;font-style:normal;font-weight:normal;line-height:110%;}h4{display:block;font-size:18px;font-style:italic;font-weight:normal;line-height:100%;}.flexibleImage{height:auto;}.linkRemoveBorder{border-bottom:0 !important;}table[class=flexibleContainerCellDivider]{padding-bottom:0 !important;padding-top:0 !important;}.main_text{font-weight:400;}p{color:#666;font-weight:400 !important;margin:0;font-size:14px;}.orange{font-weight: 400 !important}a{text-decoration:none;}strong{font-weight: 400;}body, #bodyTable{background-color:#fafafa;overflow-x:hidden;}#emailHeader{background-color:#E1E1E1;}a{text-decoration: none; color: #00a3e4;}#emailBody{background-color:#fff;border:1px solid #ddd;}#emailFooter{background-color:#E1E1E1;}.textContent, .textContentLast{color:#8B8B8B; font-family:Helvetica; font-size:16px; line-height:125%; text-align:Left;}.textContent a, .textContentLast a{color:#205478; text-decoration:underline;}.nestedContainer{background-color:#F8F8F8; border:1px solid #CCCCCC;}.emailButton{background-color:#205478; border-collapse:separate;}.buttonContent{color:#FFFFFF; font-family:Helvetica; font-size:18px; font-weight:bold; line-height:100%; padding:15px; text-align:center;}.buttonContent a{color:#FFFFFF; display:block; text-decoration:none!important; border:0!important;}.emailCalendar{background-color:#FFFFFF; border:1px solid #CCCCCC;}.emailCalendarMonth{background-color:#205478; color:#FFFFFF; font-family:Helvetica, Arial, sans-serif; font-size:16px; font-weight:bold; padding-top:10px; padding-bottom:10px; text-align:center;}.emailCalendarDay{color:#205478; font-family:Helvetica, Arial, sans-serif; font-size:60px; font-weight:bold; line-height:100%; padding-top:20px; padding-bottom:20px; text-align:center;}.imageContentText{margin-top: 10px;line-height:0;}.imageContentText a{line-height:0;}.blue{color:#333;}.main_ID{font-weight:600}#invisibleIntroduction{display:none !important;}/* Removing the introduction text from the view */ /*FRAMEWORK HACKS & OVERRIDES */ span[class=ios-color-hack] a{color:#275100!important;text-decoration:none!important;}/* Remove all link colors in IOS (below are duplicates based on the color preference) */ span[class=ios-color-hack2] a{color:#205478!important;text-decoration:none!important;}span[class=ios-color-hack3] a{color:#8B8B8B!important;text-decoration:none!important;}/* A nice and clean way to target phone numbers you want clickable and avoid a mobile phone from linking other numbers that look like, but are not phone numbers. Use these two blocks of code to "unstyle" any numbers that may be linked. The second block gives you a class to apply with a span tag to the numbers you would like linked and styled. Inspired by Campaign Monitor\'s article on using phone numbers in email: http://www.campaignmonitor.com/blog/post/3571/using-phone-numbers-in-html-email/. */ .a[href^="tel"], a[href^="sms"]{text-decoration:none!important;color:#606060!important;pointer-events:none!important;cursor:default!important;}.mobile_link a[href^="tel"], .mobile_link a[href^="sms"]{text-decoration:none!important;color:#606060!important;pointer-events:auto!important;cursor:default!important;}/* MOBILE STYLES */ @media only screen and (max-width: 480px){/*////// CLIENT-SPECIFIC STYLES //////*/ body{width:100% !important; min-width:100% !important;}/* Force iOS Mail to render the email at full width. */ /* FRAMEWORK STYLES */ /* CSS selectors are written in attribute selector format to prevent Yahoo Mail from rendering media query styles on desktop. */ table[id="emailHeader"], table[id="emailBody"], table[id="emailFooter"], table[class="flexibleContainer"]{width:100% !important;}td[class="flexibleContainerBox"], td[class="flexibleContainerBox"] table{float:left; width: 100%;text-align: center;}.text1{text-align: center;}.text2{padding-bottom: 12px; width: 100%; float: left;}.text1 img{display: inline-block !important; padding-bottom: 6px;}.m-last{width: auto;}.m-last td{border-right: 0; text-align: center !important;}.m-last td strong{padding-bottom: 12px;}.m-main-one{padding-bottom: 0 !important;}.m-last .flexibleContainerBox{padding: 0;}.m-last .flexibleContainerBox img{display: inline-block !important;}/* The following style rule makes any image classed with \'flexibleImage\' fluid when the query activates. Make sure you add an inline max-width to those images to prevent them from blowing out. */ td[class="imageContent"] img{height:auto !important; width:100% !important; max-width:100% !important;}img[class="flexibleImage"]{height:auto !important; width:100% !important;max-width:100% !important;}img[class="flexibleImageSmall"]{height:auto !important; width:auto !important;}/* Create top space for every second element in a block */ table[class="flexibleContainerBoxNext"]{padding-top: 10px !important;}/* Make buttons in the email span the full width of their container, allowing for left- or right-handed ease of use. */ table[class="emailButton"]{width:100% !important;}td[class="buttonContent"]{padding:0 !important;}td[class="buttonContent"] a{padding:15px !important;}table.threecoltable table td{padding: 15px 0;}}/* CONDITIONS FOR ANDROID DEVICES ONLY * http://developer.android.com/guide/webapps/targeting.html * http://pugetworks.com/2011/04/css-media-queries-for-targeting-different-mobile-devices/ ;=====================================================*/ @media only screen and (-webkit-device-pixel-ratio:.75){/* Put CSS for low density (ldpi) Android layouts in here */}@media only screen and (-webkit-device-pixel-ratio:1){/* Put CSS for medium density (mdpi) Android layouts in here */}@media only screen and (-webkit-device-pixel-ratio:1.5){/* Put CSS for high density (hdpi) Android layouts in here */}/* end Android targeting */ /* CONDITIONS FOR IOS DEVICES ONLY=====================================================*/ @media only screen and (min-device-width : 320px) and (max-device-width:568px){a{font-size: 12px !important;}}/* end IOS targeting */ </style><!-- Outlook Conditional CSS These two style blocks target Outlook 2007 & 2010 specifically, forcing columns into a single vertical stack as on mobile clients. This is primarily done to avoid the \'page break bug\' and is optional. More information here: http://templates.mailchimp.com/development/css/outlook-conditional-css --><!--[if mso 12]> <style type="text/css"> .flexibleContainer{display:block !important; width:100% !important;}</style><![endif]--><!--[if mso 14]> <style type="text/css"> .flexibleContainer{display:block !important; width:100% !important;}</style><![endif]--> </head> <body bgcolor="#E1E1E1" leftmargin="0" marginwidth="0" topmargin="0" marginheight="0" offset="0"> <center style="background-color:#E1E1E1;"> <table border="0" cellpadding="0" cellspacing="0" height="100%" width="100%" id="bodyTable" style="table-layout: fixed;max-width:100% !important;width: 100% !important;min-width: 100% !important;"> <tr> <td align="center" valign="top" id="bodyCell"> <table bgcolor="#FFFFFF" border="0" cellpadding="0" cellspacing="0" width="500" id="emailBody"><tr> <td valign="top"> <table border="0" cellpadding="0" cellspacing="0" width="100%" class="flexibleContainer" style="background-color: #f58634;"> <tr> <td valign="top" width="500" class="flexibleContainerCell"> <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%"> <tr> <td align="center" valign="top" class="flexibleContainerBox" style="padding:5px 0 5px;"> <table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 100%;text-align: center"> <tr> <td align="center" style="color:white;font-size: 12px;"> <strong style="color:white;display:inline;">Having trouble viewing this email? <a class="blue" href="http://gizmosmart.com"> View it in the Browser </a></strong> </td></tr></table> </td></tr></table> </td></tr></table> <table border="0" cellpadding="0" cellspacing="0" width="100%" style="color:#FFFFFF;"> <tr> <td valign="top"> <table border="0" cellpadding="0" cellspacing="0" width="100%" style=""> <tr> <td style="padding: 10px 10px 15px; background-color: #fff;float:right"> <a href="http://gizmosmart.com/"><img src="http://gizmosmart.com/demo/wp-content/uploads/2016/09/GS.png" alt="GizmoSmart | Logo"></a> </td></tr><tr> <td align="center" valign="top" class="textContent" style="border-top: 3px solid #f48134"> </td></tr></table><table border="0" cellpadding="0" cellspacing="0" width="100%" style="background:white;"><tr><td style="padding:25px 10px 0px"> <p class="main_text" style="color:#666;margin:0"> Dear '.$Name.',</p></td></tr><tr><td style="padding:10px 10px 0px"><p style="color:#666;margin:0"> Welcome to GizmoSmart, You can use below credential to Login your IoT Web Panel.</p></td></tr><tr><td style="padding:5px 10px"><p style="color:#666;margin:0"> Username: '.$userEmail.'<br>Password: '.$tempPass.'</p></td></tr><tr> <td style="padding:15px 10px 0px"> <p style="color:#666;margin:0"> To access IoT Web Panel, open the below URL in your browser. <a href="http://gizmosmart.io/iot/gizmolife_business_admin/">http://gizmosmart.io/iot/gizmolife_business_admin/ </a> </p></td></tr><tr> <td style="padding:15px 10px 0px"> <p style="color:#666;margin:0"> If you have any queries, please feel free to contact us at <a href="mailto:info@gizmosmart.com">info@gizmosmart.com. </a> We are always looking forward to serve you. </p></td></tr><tr> <td style="padding:15px 10px 0px"> <p style="color:#666;margin:0"> Take Care, </p></td></tr><tr> <td style="padding:0px 10px 25px"> <p style="color:#666;margin:0"> GizmoSmart Team </p></td></tr></table> </td></tr></table> </td></tr><tr> <td align="center" valign="top"> <table border="0" cellpadding="0" cellspacing="0" width="100%"> <tr> <td align="center" valign="top"> <table border="0" cellpadding="0" cellspacing="0" width="100%" class="flexibleContainer"> <tr> <td valign="top" width="500" class="flexibleContainerCell"> <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%"> <tr> <td align="center" valign="top" class="flexibleContainerBox"> <table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 100%;text-align: center"><tr> <td align="center" style="padding: 10px 0;font-size: 12px;color: #333;"> <a href="http://gizmosmart.com/"><strong style="border-right:1px dotted #999;color:#666;padding: 0 10px;line-height: 1.4"> www.gizmosmart.com </strong> </a><a href="mailto:info@gizmosmart.com"><strong style="border-right:1px dotted #999;color:#666;padding: 0 10px;line-height: 1.4"> info@gizmosmart.com </strong></a><a href="tel:+91 98762 98763"><strong style="color:#666;padding: 0 10px;line-height: 1.4"> +91 98762 98763 </strong></a> </td></tr><tr> <td align="center" class="" style="position:relative"> <a style="display:inline-block !important;border:0;" href="#"><img style=" width: 36px;display: inline-block !important;padding: 0 5px;" src="http://gizmosmart.com/demo/wp-content/uploads/2016/09/ic-fb.png" alt="Mail"/></a> <a style="display:inline-block !important;border:0;" href="#"><img style=" width: 36px;display: inline-block !important;padding: 0 5px;" src="http://gizmosmart.com/demo/wp-content/uploads/2016/09/ic-twitter.png" alt="Mail"/></a> <a style="display:inline-block !important;border:0;" href="#"><img style=" width: 36px;display: inline-block !important;padding: 0 5px;" src="http://gizmosmart.com/demo/wp-content/uploads/2016/09/ic-googleplus.png" alt="Mail"/></a> </td></tr><!-- <tr><td style="position:relative;"> <a href="http://gizmolife.in/"><img style="width:100%;height:auto;" src="http://gizmosmart.com/wp-content/uploads/2016/11/main.png"/> </a></td></tr>--> </table> </td></tr></table> </td></tr></table> </td></tr></table> </td></tr></table> </td></tr></table> </center> </body></html>';
							
							$params = array('to'         => $bEmail,
													 'subject'       => 'Business Account created',
													 'html'          => $account_create_html,
													 'text'          => 'test',
													 'from'          => 'GizmoSmart<no-reply@kochartech.com>'
													);
							Helpers::sendMail($params);
						}
						//Save contact person information
						$contactPersonInfo->business_id = $bussiness_id;
						$contactPersonInfo->save();
					}
				}
			}
				
           
			
            Session::flash('msg','Business User added successfully.');
			return Redirect::to('admin/business');
        }
        else{
            //Session::flash('msg','User already exits in our system or record.');
			//return Redirect::to('admin/add-business-user');
        }
    }

    public function getBusinessList(Request $request){

        $order = ($request->input("order")!=null)?$request->input("order"):"desc";
        $column = ($request->input("column")!=null)?$request->input("column"):"bussiness_id";
        $perpage = 10;
        if($request->perpage != "")
        {
          $perpage = $request->perpage;
        }
        $search="";
        if($request->searchstring != null)
        {
            $search = $request->searchstring;
        }
		
        $page = ($request->input("page")!=null)?$request->input("page"):"1";
		/* if(session()->has("page"))
        {
          $page = session()->get("page");
          session()->remove("page");
        } */
		//DB::enableQueryLog();
        $bussiness =DB::table('bussiness as b')->select('b.bussiness_id', 'b.bussiness_name', 'b.phone', 'b.email', 'c.name as country', 's.name as state', 'ci.name as city')
		->join('countries as c','c.country_id','=','b.countryid')
		->join('states as s','s.state_id','=','b.stateid') 
		->join('cities as ci','ci.city_id','=','b.cityid') 
		->where('b.status', true)
		->where('b.business_parent_id', '=', null)
		->where(function($query) use($search){
			$query->orwhere(DB::raw("lower(b.bussiness_name)"),'like','%'.strtolower($search).'%');
			$query->orwhere(DB::raw("b.phone::text"),'like','%'.strtolower($search).'%');
			$query->orwhere(DB::raw("lower(b.email)"),'like','%'.strtolower($search).'%');
			$query->orwhere(DB::raw("lower(c.name::text)"),'like','%'.strtolower($search).'%');
			$query->orwhere(DB::raw("lower(s.name::text)"),'like','%'.strtolower($search).'%');
			$query->orwhere(DB::raw("lower(ci.name::text)"),'like','%'.strtolower($search).'%');
		})
		->orderby($column, $order)
		->paginate($perpage, array("*"),"page",(isset($page)) ? $page : null);
		//print_r(DB::getQueryLog());
		return view('admin.businessadminList',['users' => $bussiness, 'order' => ($order=="asc")?"desc":"asc", 'column' => $column, 'page' => $bussiness->currentPage()]);
     
    }

    public function getBusinessdminDetails($id)
    {	/// Right Check
    	if(in_array("View", $this->assign_rights)){
    		if(!empty($id)){
				$adminDetails=DB::table('bussiness as b')
	            ->select('b.bussiness_name','b.phone','b.email','b.address','b.location','b.zipcode','b.description','b.website','b.companylogo','c.name as country','s.name as state','ci.name as city')
				->join('countries as c','c.country_id','=','b.countryid')
				->join('states as s','s.state_id','=','b.stateid') 
				->join('cities as ci','ci.city_id','=','b.cityid') 			
	            ->where('b.bussiness_id',$id)->get();
				
				$business_contact_details = DB::table('business_contact_person as bc')
				->select('bc.cp_name', 'bc.cp_email', 'bc.cp_phone', 'c.name as country','s.name as state','ci.name as city')
				->join('countries as c','c.country_id','=','bc.cp_country')
				->join('states as s','s.state_id','=','bc.cp_state') 
				->join('cities as ci','ci.city_id','=','bc.cp_city')
				->where('bc.business_id', '=', $id)
				->get();
	            return view('admin.businessadmin-detail-view', compact('adminDetails', 'business_contact_details'));
        	}
        }
        else{
        	Session::flash('msg','You are not authorized to access this location.');
    		return Redirect::to('admin/business');	
    	}
    }

    public function editBusinessAdminDetailsview($id){
    	if(in_array("Edit",$this->assign_rights)){
    		$data= DB::table('bussiness as b')
            ->select('b.bussiness_id','b.bussiness_name','b.phone','b.email','b.address','b.location','b.zipcode','b.description','b.website','b.companylogo','c.name as country','c.country_id','s.name as state','s.state_id','ci.name as city','ci.city_id','b.noofdevices','b.expirydate')
			->join('countries as c','c.country_id','=','b.countryid')
			->join('states as s','s.state_id','=','b.stateid') 
			->join('cities as ci','ci.city_id','=','b.cityid') 		
			->join('pincodes as pi','pi.pincode_id','=','b.zipcode') 	
            ->where('b.bussiness_id',$id)->get();
		$data = $data[0];
		$countries = $this->getCountry();
		$states = $this->getStatebyCountryData($data->country_id);
		$cities = $this->getCityByStateData($data->state_id);
		$pincodes = $this->getPinCodesByCityData($data->city_id);
		
		$contactPersonInfo = DB::table('business_contact_person')->where('business_id','=', $id)->get();
		$contactPersonInfo = $contactPersonInfo[0];
		$cp_states = $this->getStatebyCountryData($contactPersonInfo->cp_country);
		$cp_cities = $this->getCityByStateData($contactPersonInfo->cp_state);
		
		
        return view('admin.business-edit-view', compact('countries','states','cities', 'data','contactPersonInfo', 'pincodes','cp_states', 'cp_cities'));
    		
    	}

		
        else{
        	Session::flash('msg','You are not authorized to access this location.');
    		return Redirect::to('admin/business');	
        }
    }
    public function updateBusinessAdminDetails(request $request){
       
		$this->validate($request, [
         'bussiness_name' => 'required|max:100',
         'phone' => 'required|numeric|regex:/[0-9]{10}/',
		 //'email' => 'required',
		 'address' => 'required|max:255',
		 'country' => 'required',
		 'state' => 'required',
		 'city' => 'required',
		 'description' => 'required|max:255',
		 'website' => 'required|regex:/^(https?:\/\/)?([\da-z\.-]+)\.([A-Za-z\.]{2,6})([\/\w \.-]*)*\/?$/',
		 'expirydate' => 'required',
		 'noofdevices' => 'required|numeric',
		 'zipcode'=>'required',
		 'cp_name' => 'required|max:100',
		 'cp_email' => 'required|email',
		 'cp_phone' => 'required|numeric|regex:/[0-9]{10}/',
		 'cp_country' => 'required',
		 'cp_state' => 'required',
		 'cp_city' => 'required',
		 'companylogo' => 'max:2048|mimes:jpeg,png,jpg,gif,svg',
        ]); 
        
		//Business information 
		$business = array();
        $business['bussiness_name'] = $this->rip_tags($request['bussiness_name']);
        $business['phone'] = $request['phone'];
		$business['address']= $request['address'];
        $business['countryid']   = $request['country'];
        $business['stateid']  = $request['state'];
        $business['cityid']   = $request['city'];
        $business['description']   = $this->rip_tags($request['description']);
		$business['website']   = strtolower(trim($request['website']));
		$business['expirydate']  = $request['expirydate'];
		$business['noofdevices']   = $request['noofdevices'];
		$business['zipcode']   = $request['zipcode'];
		$business['status']   = true;
		//Business contact person information 
		$contactPersonInfo = array();
		$contactPersonInfo['cp_name'] = $this->rip_tags($request['cp_name']);
		$contactPersonInfo['cp_email'] = $request['cp_email'];
		$contactPersonInfo['cp_phone'] = $request['cp_phone'];
		$contactPersonInfo['cp_country'] = $request['cp_country'];
		$contactPersonInfo['cp_state'] = $request['cp_state'];
		$contactPersonInfo['cp_city'] = $request['cp_city'];
		//Customer information
		$customer = array();
		$customer['first_name'] = $request['bussiness_name'];
		$customer['mobile'] = $request['phone'];
		$customer['modified_date'] = date('Y-m-d h:i:s');
		$customer['address'] = $request['address'];
		
		$customer['country_id'] = $request['country'];
		$customer['state_id'] = $request['state'];
		$customer['city_id'] = $request['city'];
		$customer['pincode_id'] = $request['zipcode'];
		
		//upload Company logo
		$image = $request->file('companylogo'); 
		if(!empty($image))
		{
			
			$imageName = time().'.'.$image->getClientOriginalExtension();
			$image->move(
				base_path() . '/asset/company_logo/', $imageName
			);
			$business['companylogo'] =$imageName;
		}
		else{
			$imageName = DB::table('bussiness')->select('companylogo')->where('bussiness_id', '=', $request['bussiness_id'])->get();
			$business['companylogo'] =$imageName[0]->companylogo;
		}
		$updatebussiness = DB::table('bussiness')
		->where('bussiness_id','=', $request['bussiness_id'])
		->update($business);
		$updateContactPerson = DB::table('business_contact_person')
		->where('business_contact_id', '=',$request['business_contact_id'])
		->update($contactPersonInfo);
		
		$cust_email = DB::table('bussiness')
		->select('email')
		->where('bussiness_id', '=', $request['bussiness_id'])
		->get();
		$cust_email = $cust_email[0]->email;
		if(!empty($cust_email))
		{
			$updateCustomer = DB::table('customers')
			->where('email', '=', $cust_email)
			->update($customer);
		}
		if($updatebussiness==1 && $updateContactPerson==1 && $updateCustomer == 1)
		{
			Session::flash('msg','Business User updated successfully.');
			return Redirect::to('admin/business');
		}
	
    }
   public function businessUserCount(Request $request){
        if(!empty($request->business_id))
        {

			$customers = DB::table('customers')
			->select('count(*) as count')
			->where('business_id', '=', $request->business_id)
			->where('child_of', '!=', null)
			->where('status', '=', true)
			->count();
            echo $customers;
	   	    		

		}

    }

	/*Deactivate Business User*/
    public function deactivateBusinessUser(Request $request){
        if(!empty($request->business_id))
        {
			$customers = DB::table('customers')
			->select('cust_id')
			->where('business_id', '=', $request->business_id)
			->where('child_of', '=', null)
			->get();
			if(!empty($customers[0]->cust_id))
			{
				$customer_hubs = DB::table('customer_hubs')
				->select('*')
				->where('cust_id', '=', $customers[0]->cust_id)
				->where('status', '=', true)
				->count();
				$cust_device_hub_mappings = DB::table('cust_device_hub_mappings')
				->select('*')
				->where('cust_id', '=', $customers[0]->cust_id)
				->where('status', '=', true)
				->count();
				$independent_devices = DB::table('independent_devices')
				->select('*')
				->where('cust_id', '=', $customers[0]->cust_id)
				->where('status', '=', true)
				->count(); 
				if($customer_hubs == 0 && $cust_device_hub_mappings==0 && $independent_devices==0)
				{
					$updateBussiness = DB::table('bussiness')
					->where('bussiness_id', $request->business_id)
					->update(array("status"=>false));
					
					$businessEmail = DB::table('bussiness')
					->select('email')
					->where('bussiness_id', $request->business_id)
					->get();
					$businessEmail = $businessEmail[0]->email;
					if(!empty($businessEmail))
					{
						$updateUser = DB::table('users')
						->where('user_name','=',$businessEmail)
						->where('user_type','=',3)
						->update(array("status"=>false));
						$updateCustomer =DB::table('customers')
						->where('email','=',$businessEmail)
						->update(array("status"=>false));
                 //new changes by ankur
						$updateAllUser = DB::table('users')    
						->where('business_id','=',$request->business_id)
					    ->update(array("status"=>false));
						$updateAllCustomer =DB::table('customers')
						->where('business_id','=',$request->business_id)
						->update(array("status"=>false));

					}
					
					$Update_customer_hubs = DB::table('customer_hubs')
					->where('cust_id','=',$customers[0]->cust_id)
					->update(array("status"=>false));
					$Update_cust_device_hub_mappings = DB::table('cust_device_hub_mappings')
					->where('cust_id','=',$customers[0]->cust_id)
					->update(array("status"=>false));
					$Update_independent_devices = DB::table('independent_devices')
					->where('cust_id','=',$customers[0]->cust_id)
					->update(array("status"=>false));  
					echo 1;
				}
				else{
					echo 2;
				}
				
				
			}
        }
    }
	
	public function getCountry(){
		$country = DB::table('countries')->select('country_id','name')->get();
		return $country;
	}
	public function getState(){
		$states = DB::table('states')->select('state_id','name')->get();
		return $states;
	}
	public function getCity(){
		$cities = DB::table('cities')->select('city_id','name')->get();
		return $cities;
	}
	
	public function getPincodes(){
		$pincodes = DB::table('pincodes')->select('pincode_id','pincode')->get();
		return $pincodes;
	}
	public function getProfile(){
		$userData = Session::get('user_data');
		
		if($userData->user_type=='3')
		{
			$id = $userData->business_id;
			$data= DB::table('bussiness as b')
			->select('b.bussiness_id','b.bussiness_name as name','b.phone','b.email','b.address','b.location','b.zipcode','b.description','b.website','b.companylogo','c.name as country','c.country_id','s.name as state','s.state_id','ci.name as city','ci.city_id','b.noofdevices','b.expirydate','b.bussiness_id')
			->join('countries as c','c.country_id','=','b.countryid')
			->join('states as s','s.state_id','=','b.stateid') 
			->join('cities as ci','ci.city_id','=','b.cityid') 			
			->where('b.bussiness_id',$id)->get();
			$data = $data[0];
			
		}
		elseif($userData->user_type=='2')
		{
			$data = DB::table('customers as cust')
			->select('cust.first_name','cust.mobile','cust.email','cust.address','cust.pincode_id','c.name as country','s.name as state','ci.name as city','r.roleName as role_name','cust.country_id','cust.city_id','cust.state_id','u.role_id','cust.business_id', 'cust.child_of', 'cust.cust_id')
			->join('users as u', 'u.user_name','=', 'cust.email')
			->join('roles as r', 'r.id','=', 'u.role_id')
			->join('countries as c','c.country_id','=','cust.country_id')
			->join('states as s','s.state_id','=','cust.state_id') 
			->join('cities as ci','ci.city_id','=','cust.city_id') 
			->join('pincodes as pin', 'pin.pincode_id','=','cust.pincode_id')
			->where('u.user_type','=',2)
			->where('cust.email',$userData->user_name)->get();
			$data = $data[0];
			
		}
		return view('admin.businessprofile', compact('data'));
		
	}
	
	public function addLocation($id){
		$bussiness_id = $id;
		$countries = $this->getCountry();
		$states = $this->getState();
		$cities = $this->getCity();
		return view('admin.add_location',compact('countries', 'states', 'cities','bussiness_id'));
	}
	
	public function SaveBussinessLocation(Request $request){
		$this->validate($request, [
		 'address' => 'required|max:255',
		 'country' => 'required',
		 'state' => 'required',
		 'city' => 'required',
		 'zipcode'=>'required',
		]);
		
		$data = array();
		$data['address'] = $request['address'];
		$data['zipcode'] = $request['zipcode'];
		$data['country_id'] = $request['country'];
		$data['state_id'] = $request['state'];
		$data['city_id'] = $request['city'];
		$data['business_id'] = $request['bussiness_id'];
		
		if(!empty($request['bussiness_id']))
		{
			$businesssLocation = DB::table('business_location')->insert($data);
			if($businesssLocation)
			{
				Session::flash('msg', 'Business location added sucessfully');
				return redirect::to('admin/business');
			}
		}
	}
	
	/*get roles*/
	public function getRole($id=null){
		$user = Session::get('user_data');
		$userId=$user->user_id;
		//print_r($userId);
		$userType=$user->user_type;
		if($userType==1)
		{
			$roles = DB::table('roles')->select('id','roleName') 
			->where('id','!=',1)
			->where('id','!=',3)
			->orderBy('roleName','asc')
			->get();	
		}
		if($userType==3)
		{
			$roles = DB::table('roles')->select('id','roleName')
			->where('createdBy','=',$userId)
			->orwhere('createdBy','=',314) 
			->where('id','!=',1)
			->where('id','!=',3)
			->orderBy('roleName','asc')
			->get();	
		}
		if($userType==2)
		{
			$roles = DB::table('roles')->select('id','roleName')
			->orwhere('createdBy','=',314) 
			->where('id','!=',1)
			->where('id','!=',3)
			->orderBy('roleName','asc')
			->get();	
		}
		return $roles;
		
	}
	//Get active business
	public function getbusiness(){
		$user_data =Session::get('user_data');
		if($user_data->user_type == 1)
		{
			$business = DB::table('bussiness')
			->select('bussiness_id','bussiness_name')
			->where('status', true)
			->where('business_parent_id', '=', null)
			->get();
		}
		elseif($user_data->user_type == 3){
			$business = DB::table('bussiness')
			->select('bussiness_id','bussiness_name')
			->where('bussiness_id',$user_data->business_id)
			->where('status', true)
			->where('business_parent_id', '=', null)
			->get();
		}
		elseif($user_data->user_type == 2){
			$business = DB::table('bussiness')
			->select('bussiness_id','bussiness_name')
			->where('bussiness_id',$user_data->business_id)
			->where('status', true)
			->where('business_parent_id', '=', null)
			->get();
		}
		return $business;
	}
	
	/* Add business sub user view */
	public function addBusinessSubUserView(){
		$countries = $this->getCountry();
		$roles = $this->getRole();
		$business = $this->getbusiness();
		$reportingTo = $this->reportingTo();
		return view('admin.add_business_sub_user', compact('countries','roles', 'business', 'reportingTo'));
	}
	
	/*save business sub user */
	public function saveBusinessSubUser(Request $request)
	{

		$this->validate($request, [
		 'business_parent_id' =>'required',
		 'bussiness_name' => 'required|max:100',
         'phone' => 'required|numeric|regex:/[0-9]{10}/',
		 'email' => 'required|email',
		 'address' => 'required|max:255',
		 'country' => 'required',
		 'state' => 'required',
		 'city' => 'required',
		 'zipcode'=>'required',
		 'role_id'=>'required'
		]);
		
		//Customer information
		$customer = new customers();
		$customer->first_name = $this->rip_tags($request['bussiness_name']);
		$SUEmail = strtolower(trim($request['email']));
		$customer->email = $SUEmail;
		$customer->mobile = $request['phone'];
		$customer->status = true;
		$customer->created_date = date('Y-m-d h:i:s');
		$customer->address = $request['address'];
		$cust_email = DB::table('bussiness')
		->select('email')
		->where('bussiness_id', '=', $request['business_parent_id'])
		->get();
		$cust_email = $cust_email[0]->email;
		$cust_id = DB::table('customers')
		->select('cust_id')
		->where('email' , '=', $cust_email)
		->get();
		$customer->business_id = $request['business_parent_id'];
		$customer->child_of = $cust_id[0]->cust_id;
		$customer->country_id = $request['country'];
		$customer->state_id = $request['state'];
		$customer->city_id = $request['city'];
		$customer->pincode_id = $request['zipcode'];
		//print_r($customer);die;
		$check_user_name=DB::table('users as u')
		->select('u.user_name')
	    ->where('u.user_name', '=', $SUEmail)->get();
		$check_business_user=DB::table('bussiness as b')
		->select('b.email')
	    ->where('b.email', '=', $SUEmail)->get();
		$check_customer = DB::table('customers as c')
		->select('c.email')
	    ->where('c.email', '=', $SUEmail)->get();
        if((count($check_user_name) == 0) && (count($check_business_user) == 0) && (count($check_customer) == 0))
        {
			
					
					if($customer->save())
					{
							$user = new User();
							$user->user_name =$SUEmail;
							$randomPass = $this->random_password(8);
							$user->password =bcrypt($randomPass);
							$user->user_type =2;//for business admin sub user 
							$user->business_id =$request['business_parent_id'];
							$user->role_id = $request['role_id'];
							if(!empty($request['role_id'])){
							$cust_id = DB::table('customers')
								->select('cust_id')
								->where('email' , '=', $request['email'])
								->get();	
								}   // new change by ankur
								
							$user->cust_id = $cust_id[0]->cust_id;
							$user->reporting_to = $request['reporting_to']; 
							if($user->save())
							{
								$userEmail = $SUEmail;
								$tempPass = $randomPass;
								$Name = $request['bussiness_name'];
								$type='user';
								$urlData = base64_encode($userEmail.'@a@'.$tempPass.'@a@'.$Name.'@b@'.$type);
								$account_create_html ='<html> <head><link href="https://fonts.googleapis.com/css?family=Roboto" rel="stylesheet"><link href="https://fonts.googleapis.com/css?family=Open+Sans" rel="stylesheet"> <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" /> <meta name="viewport" content="width=device-width, initial-scale=1.0"> <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"> <meta name="format-detection" content="telephone=no" /> <title></title> <style type="text/css"> /* font roboto */ body *{font-family:"Roboto", sans-serif;font-family: "Open Sans", sans-serif;} /* RESET STYLES */ body, #bodyTable, #bodyCell, #bodyCell{height:100% !important; margin:0; padding:0; width:100% !important;font-family:Arial, "Lucida Grande", sans-serif;background-color: #fff} table{border-collapse:collapse;} table[id=bodyTable] {width:100%!important;margin:auto;max-width:500px!important;color:#7A7A7A;} img, a img{border:0; outline:none; text-decoration:none;height:auto; line-height:100%;} a {text-decoration:none !important;} h1, h2, h3, h4, h5, h6{color:#5F5F5F; font-family:Helvetica; font-size:20px; line-height:125%; text-align:Left; letter-spacing:normal;margin-top:0;margin-right:0;margin-bottom:10px;margin-left:0;padding-top:0;padding-bottom:0;padding-left:0;padding-right:0;} /* CLIENT-SPECIFIC STYLES */ .ReadMsgBody{width:100%;} .ExternalClass{width:100%;} /* Force Hotmail/Outlook.com to display emails at full width. */ .ExternalClass, .ExternalClass p, .ExternalClass span, .ExternalClass font, .ExternalClass td, .ExternalClass div{line-height:100%;} /* Force Hotmail/Outlook.com to display line heights normally. */ table, td{mso-table-lspace:0pt; mso-table-rspace:0pt;} /* Remove spacing between tables in Outlook 2007 and up. */ #outlook a{padding:0;} /* Force Outlook 2007 and up to provide a "view in browser" message. */ img{-ms-interpolation-mode: bicubic;display:block;outline:none; text-decoration:none;} /* Force IE to smoothly render resized images. */ body, table, td, p, a, li, blockquote{-ms-text-size-adjust:100%; -webkit-text-size-adjust:100%;} /* Prevent Windows- and Webkit-based mobile platforms from changing declared text sizes. */ .ExternalClass td[class="ecxflexibleContainerBox"] h3 {padding-top: 10px !important;} /* Force hotmail to push 2-grid sub headers down */ /* /\/\/\/\/\/\/\/\/ TEMPLATE STYLES /\/\/\/\/\/\/\/\/ */ /* ========== Page Styles ========== */ h1{display:block;font-size:26px;font-style:normal;font-weight:normal;line-height:100%;} h2{display:block;font-size:20px;font-style:normal;font-weight:normal;line-height:120%;} h3{display:block;font-size:17px;font-style:normal;font-weight:normal;line-height:110%;} h4{display:block;font-size:18px;font-style:italic;font-weight:normal;line-height:100%;} .flexibleImage{height:auto;} .linkRemoveBorder{border-bottom:0 !important;} table[class=flexibleContainerCellDivider] {padding-bottom:0 !important;padding-top:0 !important;}.main_text{font-weight:400;}p{color:#666;font-weight:400 !important;margin:0;font-size:14px;}.orange {font-weight: 400 !important}a{text-decoration:none;}strong {font-weight: 400;} body, #bodyTable{background-color:#fafafa;overflow-x:hidden;} #emailHeader{background-color:#E1E1E1;}a {text-decoration: none; color: #00a3e4;} #emailBody{background-color:#fff;border:1px solid #ddd;} #emailFooter{background-color:#E1E1E1;} .textContent, .textContentLast{color:#8B8B8B; font-family:Helvetica; font-size:16px; line-height:125%; text-align:Left;} .textContent a, .textContentLast a{color:#205478; text-decoration:underline;} .nestedContainer{background-color:#F8F8F8; border:1px solid #CCCCCC;} .emailButton{background-color:#205478; border-collapse:separate;} .buttonContent{color:#FFFFFF; font-family:Helvetica; font-size:18px; font-weight:bold; line-height:100%; padding:15px; text-align:center;} .buttonContent a{color:#FFFFFF; display:block; text-decoration:none!important; border:0!important;} .emailCalendar{background-color:#FFFFFF; border:1px solid #CCCCCC;} .emailCalendarMonth{background-color:#205478; color:#FFFFFF; font-family:Helvetica, Arial, sans-serif; font-size:16px; font-weight:bold; padding-top:10px; padding-bottom:10px; text-align:center;} .emailCalendarDay{color:#205478; font-family:Helvetica, Arial, sans-serif; font-size:60px; font-weight:bold; line-height:100%; padding-top:20px; padding-bottom:20px; text-align:center;} .imageContentText {margin-top: 10px;line-height:0;} .imageContentText a {line-height:0;}.blue{color:#333;}.main_ID{font-weight:600} #invisibleIntroduction {display:none !important;} /* Removing the introduction text from the view */ /*FRAMEWORK HACKS & OVERRIDES */ span[class=ios-color-hack] a {color:#275100!important;text-decoration:none!important;} /* Remove all link colors in IOS (below are duplicates based on the color preference) */ span[class=ios-color-hack2] a {color:#205478!important;text-decoration:none!important;} span[class=ios-color-hack3] a {color:#8B8B8B!important;text-decoration:none!important;} .a[href^="tel"], a[href^="sms"] {text-decoration:none!important;color:#606060!important;pointer-events:none!important;cursor:default!important;} .mobile_link a[href^="tel"], .mobile_link a[href^="sms"] {text-decoration:none!important;color:#606060!important;pointer-events:auto!important;cursor:default!important;} /* MOBILE STYLES */ @media only screen and (max-width: 480px){  body{width:100% !important; min-width:100% !important;} /* Force iOS Mail to render the email at full width. */ /* FRAMEWORK STYLES */ /* CSS selectors are written in attribute selector format to prevent Yahoo Mail from rendering media query styles on desktop. */ table[id="emailHeader"], table[id="emailBody"], table[id="emailFooter"], table[class="flexibleContainer"] {width:100% !important;} td[class="flexibleContainerBox"], td[class="flexibleContainerBox"] table {float:left; width: 100%;text-align: center;} .text1 { text-align: center; } .text2 { padding-bottom: 12px; width: 100%; float: left; } .text1 img { display: inline-block !important; padding-bottom: 6px; } .m-last { width: auto; } .m-last td { border-right: 0; text-align: center !important; } .m-last td strong { padding-bottom: 12px; } .m-main-one { padding-bottom: 0 !important; } .m-last .flexibleContainerBox { padding: 0; } .m-last .flexibleContainerBox img { display: inline-block !important; } td[class="imageContent"] img {height:auto !important; width:100% !important; max-width:100% !important;} img[class="flexibleImage"]{height:auto !important; width:100% !important;max-width:100% !important;} img[class="flexibleImageSmall"]{height:auto !important; width:auto !important;} /* Create top space for every second element in a block */ table[class="flexibleContainerBoxNext"]{padding-top: 10px !important;} /* Make buttons in the email span the full width of their container, allowing for left- or right-handed ease of use. */ table[class="emailButton"]{width:100% !important;} td[class="buttonContent"]{padding:0 !important;} td[class="buttonContent"] a{padding:15px !important;}table.threecoltable table td {padding: 15px 0;} } /* CONDITIONS FOR ANDROID DEVICES ONLY * http://developer.android.com/guide/webapps/targeting.html * http://pugetworks.com/2011/04/css-media-queries-for-targeting-different-mobile-devices/ ; =====================================================*/ @media only screen and (-webkit-device-pixel-ratio:.75){ /* Put CSS for low density (ldpi) Android layouts in here */ } @media only screen and (-webkit-device-pixel-ratio:1){ /* Put CSS for medium density (mdpi) Android layouts in here */ } @media only screen and (-webkit-device-pixel-ratio:1.5){ /* Put CSS for high density (hdpi) Android layouts in here */ } /* end Android targeting */ /* CONDITIONS FOR IOS DEVICES ONLY =====================================================*/ @media only screen and (min-device-width : 320px) and (max-device-width:568px) { } /* end IOS targeting */ </style> </head> <body bgcolor="#E1E1E1" leftmargin="0" marginwidth="0" topmargin="0" marginheight="0" offset="0"> <center style="background-color:#E1E1E1;"> <table border="0" cellpadding="0" cellspacing="0" height="100%" width="100%" id="bodyTable" style="table-layout: fixed;max-width:100% !important;width: 100% !important;min-width: 100% !important;"> <tr> <td align="center" valign="top" id="bodyCell"> <table bgcolor="#FFFFFF" border="0" cellpadding="0" cellspacing="0" width="500" id="emailBody"><tr> <td valign="top"> <table border="0" cellpadding="0" cellspacing="0" width="100%" class="flexibleContainer" style="background-color: #f58634;"> <tr> <td valign="top" width="500" class="flexibleContainerCell"> <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%"> <tr> <td align="center" valign="top" class="flexibleContainerBox" style="padding:5px 0 5px;"> <table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 100%;text-align: center"> <tr> <td align="center" style="color:white;font-size: 12px;"> <strong style="color:white;display:inline;">Having trouble viewing this email? <a class="blue" href="http://gizmosmart.io/iot/gizmolife_business_admin/view-email/'.$urlData.'"> View it in the Browser </a></strong> </td> </tr> </table> </td> </tr> </table> </td> </tr> </table> <table border="0" cellpadding="0" cellspacing="0" width="100%" style="color:#FFFFFF;"> <tr> <td valign="top"> <table border="0" cellpadding="0" cellspacing="0" width="100%" style=""> <tr> <td style="padding: 10px 10px 15px; background-color: #fff;float:right"> <a href="http://gizmosmart.com/"><img src="http://gizmosmart.com/demo/wp-content/uploads/2016/09/GS.png" alt="GizmoSmart | Logo"></a> </td> </tr> <tr> <td align="center" valign="top" class="textContent" style="border-top: 3px solid #f48134"> </td> </tr> </table><table border="0" cellpadding="0" cellspacing="0" width="100%" style="background:white;"><tr><td style="padding:25px 10px 0px"> <p class="main_text" style="color:#666;margin:0"> Dear '.$Name.', </p></td></tr><tr><td style="padding:10px 10px 0px"><p style="color:#666;margin:0"> Welcome to GizmoSmart, You can use below credential to Login your IoT Web Panel.</p> </td></tr><tr><td style="padding:5px 10px"><p style="color:#666;margin:0"> Username: '.$userEmail.'<br>Password: '.$tempPass.'</p> </td></tr><tr><td style="padding:15px 10px 0px"> <p style="color:#666;margin:0"> To access IoT Web Panel, open the below URL in your browser.<a href="http://gizmosmart.io/iot/gizmolife_business_admin/">http://gizmosmart.io/iot/gizmolife_business_admin/</a> </p></td></tr><tr><td style="padding:15px 10px 0px"> <p style="color:#666;margin:0"> If you have any queries, please feel free to contact us at <a href="mailto:info@gizmosmart.com">info@gizmosmart.com.</a> We are always looking forward to serve you. </p></td></tr><tr><td style="padding:15px 10px 0px"> <p style="color:#666;margin:0"> Take Care, </p></td></tr><tr><td style="padding:0px 10px 25px"><p style="color:#666;margin:0"> GizmoSmart Team </p> </td></tr></table> </td> </tr> </table> </td> </tr> <tr> <td align="center" valign="top"> <table border="0" cellpadding="0" cellspacing="0" width="100%"> <tr> <td align="center" valign="top"> <table border="0" cellpadding="0" cellspacing="0" width="100%" class="flexibleContainer"> <tr> <td valign="top" width="500" class="flexibleContainerCell"> <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%"> <tr> <td align="center" valign="top" class="flexibleContainerBox"> <table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 100%;text-align: center"><tr> <td align="center" style="padding: 10px 0;font-size: 12px;color: #333;"> <a href="http://gizmosmart.com/"><strong style="border-right:1px dotted #999;color:#666;padding: 0 10px;line-height: 1.4"> www.gizmosmart.com </strong> </a><a href="mailto:info@gizmosmart.com"><strong style="border-right:1px dotted #999;color:#666;padding: 0 10px;line-height: 1.4"> info@gizmosmart.com </strong></a><a href="tel:+91 98762 98763"><strong style="color:#666;padding: 0 10px;line-height: 1.4"> +91 98762 98763 </strong></a> </td> </tr> <tr> <td align="center" class="" style="position:relative"> <a style= "display:inline-block !important;border:0;" href = "#"><img style= " width: 36px;display: inline-block !important;padding: 0 5px;" src="http://gizmosmart.com/demo/wp-content/uploads/2016/09/ic-fb.png" alt="Mail"/></a> <a style= "display:inline-block !important;border:0;" href = "#"><img style= " width: 36px;display: inline-block !important;padding: 0 5px;" src="http://gizmosmart.com/demo/wp-content/uploads/2016/09/ic-twitter.png" alt="Mail"/></a> <a style= "display:inline-block !important;border:0;" href = "#"><img style= " width: 36px;display: inline-block !important;padding: 0 5px;" src="http://gizmosmart.com/demo/wp-content/uploads/2016/09/ic-googleplus.png" alt="Mail"/></a> </td> </tr> </table> </td> </tr> </table> </td> </tr> </table> </td> </tr> </table> </td> </tr> </table> </td> </tr> </table> </center> </body></html>';
								
								$params = array('to'         => $SUEmail,
														 'subject'       => 'User Account created',
														 'html'          => $account_create_html,
														 'text'          => 'test',
														 'from'          => 'GizmoSmart<no-reply@kochartech.com>'
														);
								Helpers::sendMail($params);
							}
							Session::flash('msg','Business Sub User added successfully.');
							return Redirect::to('admin/business-sub-user');
					}
				
				
			
		}
		else{
            Session::flash('msg','User already exits in our system or record.');
			return Redirect::to('admin/add-sub-user');
        }
	}
	
	/*get business sub user list*/
	public function getBusinessSubuserList(Request $request){
		$user_data =Session::get('user_data');
		//print_r($user_data);die;
		$cust_id = DB::table('customers')
		->select('cust_id')
		->where('business_id' , '=', $user_data->business_id)
		->where('child_of', '=', null)
		->get();
		//print_r($user_data->business_id);die;
		
		$order = ($request->input("order")!=null)?$request->input("order"):"desc";
        $column = ($request->input("column")!=null)?$request->input("column"):"cust_id";
        $perpage = 10;
        if($request->perpage != "")
        {
          $perpage = $request->perpage;
        }
        $search="";
        if($request->searchstring != null)
        {
            $search = $request->searchstring;
        }
        $page = ($request->input("page")!=null)?$request->input("page"):"1";
		
		if($user_data->user_type==1)
		{
			$bussiness =DB::table('customers as cust')
			->join('users as us','us.cust_id','=','cust.cust_id')
			->where('cust.status', true)
			->where('us.status', true)
			->where('cust.child_of', '!=', null)
			->where(function($query) use($search){
				$query->where(DB::raw('lower("first_name")'),'like','%'.strtolower($search).'%');
				$query->orwhere(DB::raw("mobile::text"),'like','%'.$search.'%');
				$query->orwhere(DB::raw("email"),'like','%'.strtolower($search).'%');
			})
			->orderby("cust".".".$column, $order)
			->paginate($perpage, array("*"),"page",(isset($page)) ? $page : null);
			return view('admin.business_sub_userList',['users' => $bussiness, 'order' => ($order=="asc")?"desc":"asc", 'column' => $column, 'page' => $bussiness->currentPage()]);
		} 
		elseif($user_data->user_type==3)
		{
			$bussiness =DB::table('customers as cust')
			->join('users as us','us.cust_id','=','cust.cust_id')
			->where('cust.status', true)
			->where('us.status', true)
			//->where('child_of', '!=', null)
			->where('cust.business_id', $user_data->business_id)
			->where('cust.email', '!=', $user_data->user_name)
			//->where('child_of', '=', $cust_id)
			->where(function($query) use($search){
				$query->where(DB::raw('lower("first_name")'),'like','%'.strtolower($search).'%');
				$query->orwhere(DB::raw("mobile::text"),'like','%'.$search.'%');
				$query->orwhere(DB::raw("email"),'like','%'.strtolower($search).'%');
			})
			->orderby("cust".".".$column, $order)
			->paginate($perpage, array("*"),"page",(isset($page)) ? $page : null);
			
			return view('admin.business_sub_userList',['users' => $bussiness, 'order' => ($order=="asc")?"desc":"asc", 'column' => $column, 'page' => $bussiness->currentPage()]);
		}//new changes by ankur
		elseif($user_data->user_type){
			
				$cust_id = DB::table('customers')
								->select('cust_id')
								->where('email' , '=', $user_data->user_name)
								->get();

	            $bussinessusers=DB::table('users')
	                             ->select('user_name')
	                              ->where('status', true)
	                              ->where('reporting_to',$cust_id[0]->cust_id)
	                              ->get();
	             $bussinessusers=$this->object2array($bussinessusers);
	             $oneDimensionalArray = array_map('current', $bussinessusers);
	

                 $custdetail = DB::table('customers')
								//->select('cust_id')
								->where('status', true)
								->whereIn('email' ,$oneDimensionalArray)
								->where(function($query) use($search){
							$query->where(DB::raw('lower("first_name")'),'like','%'.strtolower($search).'%');
							$query->orwhere(DB::raw("mobile::text"),'like','%'.$search.'%');
							$query->orwhere(DB::raw("email"),'like','%'.strtolower($search).'%');
						})
						 ->orderBy($column, $order)
						->paginate($perpage, array("*"),"page",(isset($page)) ? $page : null);
			
	              return view('admin.business_sub_userList',['users' => $custdetail, 'order' => ($order=="asc")?"desc":"asc", 'column' => $column, 'page' => $custdetail->currentPage()]);             

		}
	}
	
	/*get business sub user Details */
	public function getBusinessSubUserDetails($id){
		if(!empty($id))
        {
			$cust_id = DB::table('customers')
			->select('cust_id', 'email')
			->where('cust_id' , '=', $id)
			->get();
			$cust_email = $cust_id[0]->email;
			//DB::getQueryLog();
			$subUserDetails=DB::table('customers as cust')
            ->select('cust.first_name','cust.mobile','cust.email','cust.address','pin.pincode','c.name as country','s.name as state','ci.name as city','r.roleName as role_name')
			->join('users as u', 'u.user_name','=', 'cust.email')
			->join('roles as r', 'r.id','=', 'u.role_id')
			->join('countries as c','c.country_id','=','cust.country_id')
			->join('states as s','s.state_id','=','cust.state_id') 
			->join('cities as ci','ci.city_id','=','cust.city_id') 
			->join('pincodes as pin', 'pin.pincode_id','=','cust.pincode_id')
			->where('u.user_type','=',2)
            ->where('cust.email',$cust_email)->get();
			//print_r(DB::enableQueryLog());
			
			return view('admin.business-sub-user-detail-view', compact('subUserDetails'));
        }
		
	}
	
	/*update subuser form view*/
	public function editBusinessSubUserview($id){
		
		if(!empty($id))
		{
			$user_data =Session::get('user_data');
		    $user_type=$user_data->user_type; //new change
			$cust_id = DB::table('customers')
			->select('cust_id', 'email')
			->where('cust_id' , '=', $id)
			->get();
			$cust_email = $cust_id[0]->email;
			$countries = $this->getCountry();
			$roles = $this->getRole();
			$business = $this->getbusiness();
			$subUserDetails=DB::table('customers as cust')
			->select('cust.first_name','cust.mobile','cust.email','cust.address','cust.pincode_id','c.name as country','s.name as state','ci.name as city','r.roleName as role_name','cust.country_id','cust.city_id','cust.state_id','u.role_id','cust.business_id', 'cust.child_of', 'cust.cust_id', 'u.reporting_to')
			->join('users as u', 'u.user_name','=', 'cust.email')
			->join('roles as r', 'r.id','=', 'u.role_id')
			->join('countries as c','c.country_id','=','cust.country_id')
			->join('states as s','s.state_id','=','cust.state_id') 
			->join('cities as ci','ci.city_id','=','cust.city_id') 
			->join('pincodes as pin', 'pin.pincode_id','=','cust.pincode_id')
			->where('u.user_type','=',2)
			->where('cust.email',$cust_email)->get();
			//print_r($subUserDetails);die;
			$subUserDetails = $subUserDetails[0];
			$states = $this->getStatebyCountryData($subUserDetails->country_id);
			$cities = $this->getCityByStateData($subUserDetails->state_id);
			$pincodes = $this->getPinCodesByCityData($subUserDetails->city_id);
			$reportingTo = $this->reportingTo($id);
			return view('admin.edit_business_sub_user', compact('countries', 'states', 'cities','roles', 'business','subUserDetails', 'pincodes', 'reportingTo','user_type'));
		}
	}
	
	/*update sub user */
	public function updateBusinessSubUser(Request $request)
	{
		$this->validate($request, [
		 'business_parent_id' =>'required',
		 'bussiness_name' => 'required|max:100',
         'phone' => 'required|numeric|regex:/[0-9]{10}/',
		 'address' => 'required|max:255',
		 'country' => 'required',
		 'state' => 'required',
		 'city' => 'required',
		 'zipcode'=>'required',
		 'role_id'=>'required'
		]);
		
		/* $business = array();
        $business['bussiness_name']  = $this->rip_tags($request['bussiness_name']);
		$business['phone'] = $request['phone'];
        $business['address']= $this->rip_tags($request['address']);
		$business['countryid']   = $request['country'];
		$business['stateid']  = $request['state'];
        $business['cityid']   = $request['city'];
        $business['zipcode']   = $request['zipcode'];
		$business['business_parent_id']   = $request['business_parent_id']; */
		
		/* $updatebussiness = DB::table('bussiness')
		->where('bussiness_id','=', $request['business_id'])
		->update($business);
		 */
		$customer = array();
		$customer['first_name'] = $this->rip_tags($request['bussiness_name']);
		$customer['mobile'] = $request['phone'];
		$customer['modified_date'] = date('Y-m-d h:i:s');
		$customer['address'] = $this->rip_tags($request['address']);
		$customer['country_id']   = $request['country'];
		$customer['state_id']  = $request['state'];
        $customer['city_id']   = $request['city'];
        $customer['pincode_id']   = $request['zipcode'];
		$customer['business_id']   = $request['business_parent_id'];
		
		if(!empty( $request['cust_id']))
		{
			$cust_email = DB::table('bussiness')
			->select('email')
			->where('bussiness_id', '=', $request['business_parent_id'])
			->get();
			$cust_email = $cust_email[0]->email;
			$cust_id = DB::table('customers')
			->select('cust_id')
			->where('email' , '=', $cust_email)
			->get();	
			$customer['child_of'] =$cust_id[0]->cust_id;
			$updateCustomer = DB::table('customers')
			->where('cust_id','=', $request['cust_id'])
			->update($customer);
			
			$cust_email= DB::table('customers')
			->select('cust_id', 'email')
			->where('cust_id' , '=', $request['cust_id'])
			->get();
			$cust_email = $cust_email[0]->email;
			if(!empty($cust_email))
			{
				
				$user2 = array();
				$user2['role_id'] = $request['role_id'];
                 
					if(!empty($request['role_id'])){
							$cust_id = DB::table('customers')
								->select('cust_id')
								->where('email' , '=', $cust_email)
								->get();	
						}   // new change by ankur
				$user2['cust_id'] = $cust_id[0]->cust_id;
				$user2['reporting_to'] =  $request['reporting_to'];
				$updateuser = DB::table('users')
				->where('user_name','=', $cust_email)
				->where('user_type','=',2)
				->update($user2);
			}
			Session::flash('msg','Sub User Updated successfully.');
			return Redirect::to('admin/business-sub-user');
		}

	}
	
	/*De- activate sub user*/
		public function deactivateSubUser(Request $request){
        if(!empty($request->cust_id))
        {

        
            $customers = DB::table('customers')->select('cust_id', 'email','first_name','business_id')->where('cust_id', '=', $request->cust_id)->get();
			if(!empty($customers[0]->cust_id))
			{

				$customer_hubs = DB::table('customer_hubs')
				->select('*')
				->where('cust_id', '=', $request->cust_id)
				->where('status', '=', true)
				->count();
				$cust_device_hub_mappings = DB::table('cust_device_hub_mappings')
				->select('*')
				->where('cust_id', '=', $request->cust_id)
				->where('status', '=', true)
				->count();
				$independent_devices = DB::table('independent_devices')
				->select('*')
				->where('cust_id', '=', $request->cust_id)
				->where('status', '=', true)
				->count();
				$checkreporting = DB::table('users')
				->select('count(*) as count')
				->where('reporting_to', '=', $request->cust_id)
				->count(); 
			if($checkreporting == 0){					
			if($customer_hubs == 0 && $cust_device_hub_mappings==0 && $independent_devices==0)
				{
					if(!empty($customers[0]->email))
					{
						$updateUser = DB::table('users')
						->where('user_name','=',$customers[0]->email)
						->where('user_type','=',2)
						->update(array("status"=>false));
					}
					$updateCustomer =DB::table('customers')
					->where('cust_id','=',$request->cust_id)
					->update(array("status"=>false));
					$Update_customer_hubs = DB::table('customer_hubs')
					->where('cust_id','=',$customers[0]->cust_id)
					->update(array("status"=>false));
					$Update_cust_device_hub_mappings = DB::table('cust_device_hub_mappings')
					->where('cust_id','=',$customers[0]->cust_id)
					->update(array("status"=>false));
					$Update_independent_devices = DB::table('independent_devices')
					->where('cust_id','=',$customers[0]->cust_id)
					->update(array("status"=>false));
                    $businessEmail =DB::table('bussiness')
                     ->select('email')
					 ->where('bussiness_id','=',$customers[0]->business_id)
					 ->where('status',true)
					 ->get();
				   $arrayEmail=[$customers[0]->email,$businessEmail[0]->email];
			        
           foreach ($arrayEmail as $email){
			   //$urlData = base64_encode($userEmail.'@a@'.$tempPass.'@a@'.$Name.'@b@'.$type);
			   $account_html ='<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"><html> <head><link href="https://fonts.googleapis.com/css?family=Roboto" rel="stylesheet"><link href="https://fonts.googleapis.com/css?family=Open+Sans" rel="stylesheet"> <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/> <meta name="viewport" content="width=device-width, initial-scale=1.0"> <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"> <meta name="format-detection" content="telephone=no"/> <title></title> <style type="text/css"> /* font roboto */ body *{font-family:\'Roboto\', sans-serif;font-family: \'Open Sans\', sans-serif;}/* RESET STYLES */ body, #bodyTable, #bodyCell, #bodyCell{height:100% !important; margin:0; padding:0; width:100% !important;font-family:Arial, "Lucida Grande", sans-serif;background-color: #fff}table{border-collapse:collapse;}table[id=bodyTable]{width:100%!important;margin:auto;max-width:500px!important;color:#7A7A7A;}img, a img{border:0; outline:none; text-decoration:none;height:auto; line-height:100%;}a{text-decoration:none !important;}h1, h2, h3, h4, h5, h6{color:#5F5F5F; font-family:Helvetica; font-size:20px; line-height:125%; text-align:Left; letter-spacing:normal;margin-top:0;margin-right:0;margin-bottom:10px;margin-left:0;padding-top:0;padding-bottom:0;padding-left:0;padding-right:0;}/* CLIENT-SPECIFIC STYLES */ .ReadMsgBody{width:100%;}.ExternalClass{width:100%;}/* Force Hotmail/Outlook.com to display emails at full width. */ .ExternalClass, .ExternalClass p, .ExternalClass span, .ExternalClass font, .ExternalClass td, .ExternalClass div{line-height:100%;}/* Force Hotmail/Outlook.com to display line heights normally. */ table, td{mso-table-lspace:0pt; mso-table-rspace:0pt;}/* Remove spacing between tables in Outlook 2007 and up. */ #outlook a{padding:0;}/* Force Outlook 2007 and up to provide a "view in browser" message. */ img{-ms-interpolation-mode: bicubic;display:block;outline:none; text-decoration:none;}/* Force IE to smoothly render resized images. */ body, table, td, p, a, li, blockquote{-ms-text-size-adjust:100%; -webkit-text-size-adjust:100%;}/* Prevent Windows- and Webkit-based mobile platforms from changing declared text sizes. */ .ExternalClass td[class="ecxflexibleContainerBox"] h3{padding-top: 10px !important;}/* Force hotmail to push 2-grid sub headers down */ /* /\/\/\/\/\/\/\/\/ TEMPLATE STYLES /\/\/\/\/\/\/\/\/ */ /*==========Page Styles==========*/ h1{display:block;font-size:26px;font-style:normal;font-weight:normal;line-height:100%;}h2{display:block;font-size:20px;font-style:normal;font-weight:normal;line-height:120%;}h3{display:block;font-size:17px;font-style:normal;font-weight:normal;line-height:110%;}h4{display:block;font-size:18px;font-style:italic;font-weight:normal;line-height:100%;}.flexibleImage{height:auto;}.linkRemoveBorder{border-bottom:0 !important;}table[class=flexibleContainerCellDivider]{padding-bottom:0 !important;padding-top:0 !important;}.main_text{font-weight:400;}p{color:#666;font-weight:400 !important;margin:0;font-size:14px;}.orange{font-weight: 400 !important}a{text-decoration:none;}strong{font-weight: 400;}body, #bodyTable{background-color:#fafafa;overflow-x:hidden;}#emailHeader{background-color:#E1E1E1;}a{text-decoration: none; color: #00a3e4;}#emailBody{background-color:#fff;border:1px solid #ddd;}#emailFooter{background-color:#E1E1E1;}.textContent, .textContentLast{color:#8B8B8B; font-family:Helvetica; font-size:16px; line-height:125%; text-align:Left;}.textContent a, .textContentLast a{color:#205478; text-decoration:underline;}.nestedContainer{background-color:#F8F8F8; border:1px solid #CCCCCC;}.emailButton{background-color:#205478; border-collapse:separate;}.buttonContent{color:#FFFFFF; font-family:Helvetica; font-size:18px; font-weight:bold; line-height:100%; padding:15px; text-align:center;}.buttonContent a{color:#FFFFFF; display:block; text-decoration:none!important; border:0!important;}.emailCalendar{background-color:#FFFFFF; border:1px solid #CCCCCC;}.emailCalendarMonth{background-color:#205478; color:#FFFFFF; font-family:Helvetica, Arial, sans-serif; font-size:16px; font-weight:bold; padding-top:10px; padding-bottom:10px; text-align:center;}.emailCalendarDay{color:#205478; font-family:Helvetica, Arial, sans-serif; font-size:60px; font-weight:bold; line-height:100%; padding-top:20px; padding-bottom:20px; text-align:center;}.imageContentText{margin-top: 10px;line-height:0;}.imageContentText a{line-height:0;}.blue{color:#333;}.main_ID{font-weight:600}#invisibleIntroduction{display:none !important;}/* Removing the introduction text from the view */ /*FRAMEWORK HACKS & OVERRIDES */ span[class=ios-color-hack] a{color:#275100!important;text-decoration:none!important;}/* Remove all link colors in IOS (below are duplicates based on the color preference) */ span[class=ios-color-hack2] a{color:#205478!important;text-decoration:none!important;}span[class=ios-color-hack3] a{color:#8B8B8B!important;text-decoration:none!important;}/* A nice and clean way to target phone numbers you want clickable and avoid a mobile phone from linking other numbers that look like, but are not phone numbers. Use these two blocks of code to "unstyle" any numbers that may be linked. The second block gives you a class to apply with a span tag to the numbers you would like linked and styled. Inspired by Campaign Monitor\'s article on using phone numbers in email: http://www.campaignmonitor.com/blog/post/3571/using-phone-numbers-in-html-email/. */ .a[href^="tel"], a[href^="sms"]{text-decoration:none!important;color:#606060!important;pointer-events:none!important;cursor:default!important;}.mobile_link a[href^="tel"], .mobile_link a[href^="sms"]{text-decoration:none!important;color:#606060!important;pointer-events:auto!important;cursor:default!important;}/* MOBILE STYLES */ @media only screen and (max-width: 480px){/*////// CLIENT-SPECIFIC STYLES //////*/ body{width:100% !important; min-width:100% !important;}/* Force iOS Mail to render the email at full width. */ /* FRAMEWORK STYLES */ /* CSS selectors are written in attribute selector format to prevent Yahoo Mail from rendering media query styles on desktop. */ table[id="emailHeader"], table[id="emailBody"], table[id="emailFooter"], table[class="flexibleContainer"]{width:100% !important;}td[class="flexibleContainerBox"], td[class="flexibleContainerBox"] table{float:left; width: 100%;text-align: center;}.text1{text-align: center;}.text2{padding-bottom: 12px; width: 100%; float: left;}.text1 img{display: inline-block !important; padding-bottom: 6px;}.m-last{width: auto;}.m-last td{border-right: 0; text-align: center !important;}.m-last td strong{padding-bottom: 12px;}.m-main-one{padding-bottom: 0 !important;}.m-last .flexibleContainerBox{padding: 0;}.m-last .flexibleContainerBox img{display: inline-block !important;}/* The following style rule makes any image classed with \'flexibleImage\' fluid when the query activates. Make sure you add an inline max-width to those images to prevent them from blowing out. */ td[class="imageContent"] img{height:auto !important; width:100% !important; max-width:100% !important;}img[class="flexibleImage"]{height:auto !important; width:100% !important;max-width:100% !important;}img[class="flexibleImageSmall"]{height:auto !important; width:auto !important;}/* Create top space for every second element in a block */ table[class="flexibleContainerBoxNext"]{padding-top: 10px !important;}/* Make buttons in the email span the full width of their container, allowing for left- or right-handed ease of use. */ table[class="emailButton"]{width:100% !important;}td[class="buttonContent"]{padding:0 !important;}td[class="buttonContent"] a{padding:15px !important;}table.threecoltable table td{padding: 15px 0;}}/* CONDITIONS FOR ANDROID DEVICES ONLY * http://developer.android.com/guide/webapps/targeting.html * http://pugetworks.com/2011/04/css-media-queries-for-targeting-different-mobile-devices/ ;=====================================================*/ @media only screen and (-webkit-device-pixel-ratio:.75){/* Put CSS for low density (ldpi) Android layouts in here */}@media only screen and (-webkit-device-pixel-ratio:1){/* Put CSS for medium density (mdpi) Android layouts in here */}@media only screen and (-webkit-device-pixel-ratio:1.5){/* Put CSS for high density (hdpi) Android layouts in here */}/* end Android targeting */ /* CONDITIONS FOR IOS DEVICES ONLY=====================================================*/ @media only screen and (min-device-width : 320px) and (max-device-width:568px){a{font-size: 12px !important;}}/* end IOS targeting */ </style><!-- Outlook Conditional CSS These two style blocks target Outlook 2007 & 2010 specifically, forcing columns into a single vertical stack as on mobile clients. This is primarily done to avoid the \'page break bug\' and is optional. More information here: http://templates.mailchimp.com/development/css/outlook-conditional-css --><!--[if mso 12]> <style type="text/css"> .flexibleContainer{display:block !important; width:100% !important;}</style><![endif]--><!--[if mso 14]> <style type="text/css"> .flexibleContainer{display:block !important; width:100% !important;}</style><![endif]--> </head> <body bgcolor="#E1E1E1" leftmargin="0" marginwidth="0" topmargin="0" marginheight="0" offset="0"> <center style="background-color:#E1E1E1;"> <table border="0" cellpadding="0" cellspacing="0" height="100%" width="100%" id="bodyTable" style="table-layout: fixed;max-width:100% !important;width: 100% !important;min-width: 100% !important;"> <tr> <td align="center" valign="top" id="bodyCell"> <table bgcolor="#FFFFFF" border="0" cellpadding="0" cellspacing="0" width="500" id="emailBody"><tr> <td valign="top"> <table border="0" cellpadding="0" cellspacing="0" width="100%" class="flexibleContainer" style="background-color: #f58634;"> <tr> <td valign="top" width="500" class="flexibleContainerCell"> <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%"> <tr> <td align="center" valign="top" class="flexibleContainerBox" style="padding:5px 0 5px;"> <table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 100%;text-align: center"> <tr> <td align="center" style="color:white;font-size: 12px;"> <strong style="color:white;display:inline;">Having trouble viewing this email? <a class="blue" href="http://gizmosmart.com"> View it in the Browser </a></strong> </td></tr></table> </td></tr></table> </td></tr></table> <table border="0" cellpadding="0" cellspacing="0" width="100%" style="color:#FFFFFF;"> <tr> <td valign="top"> <table border="0" cellpadding="0" cellspacing="0" width="100%" style=""> <tr> <td style="padding: 10px 10px 15px; background-color: #fff;float:right"> <a href="http://gizmosmart.com/"><img src="http://gizmosmart.com/demo/wp-content/uploads/2016/09/GS.png" alt="GizmoSmart | Logo"></a> </td></tr><tr> <td align="center" valign="top" class="textContent" style="border-top: 3px solid #f48134"> </td></tr></table><table border="0" cellpadding="0" cellspacing="0" width="100%" style="background:white;"><tr><td style="padding:25px 10px 0px"> <p class="main_text" style="color:#666;margin:0"> Dear '.$customers[0]->first_name.',</p></td></tr><tr><td style="padding:10px 10px 0px"><p style="color:#666;margin:0"> Welcome to GizmoSmart, This is to inform you that your account has been deactivate.</p></td></tr><tr><td style="padding:5px 10px"><p style="color:#666;margin:0"></p></td></tr><!--tr> <td style="padding:15px 10px 0px"> <p style="color:#666;margin:0"> To access IoT Web Panel, open the below URL in your browser. <a href="http://gizmosmart.io/iot/gizmolife_business_admin/">http://gizmosmart.io/iot/gizmolife_business_admin/ </a> </p></td></tr--><tr> <td style="padding:15px 10px 0px"> <p style="color:#666;margin:0"> If you have any queries, please feel free to contact us at <a href="mailto:info@gizmosmart.com">info@gizmosmart.com. </a> We are always looking forward to serve you. </p></td></tr><tr> <td style="padding:15px 10px 0px"> <p style="color:#666;margin:0"> Take Care, </p></td></tr><tr> <td style="padding:0px 10px 25px"> <p style="color:#666;margin:0"> GizmoSmart Team </p></td></tr></table> </td></tr></table> </td></tr><tr> <td align="center" valign="top"> <table border="0" cellpadding="0" cellspacing="0" width="100%"> <tr> <td align="center" valign="top"> <table border="0" cellpadding="0" cellspacing="0" width="100%" class="flexibleContainer"> <tr> <td valign="top" width="500" class="flexibleContainerCell"> <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%"> <tr> <td align="center" valign="top" class="flexibleContainerBox"> <table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 100%;text-align: center"><tr> <td align="center" style="padding: 10px 0;font-size: 12px;color: #333;"> <a href="http://gizmosmart.com/"><strong style="border-right:1px dotted #999;color:#666;padding: 0 10px;line-height: 1.4"> www.gizmosmart.com </strong> </a><a href="mailto:info@gizmosmart.com"><strong style="border-right:1px dotted #999;color:#666;padding: 0 10px;line-height: 1.4"> info@gizmosmart.com </strong></a><a href="tel:+91 98762 98763"><strong style="color:#666;padding: 0 10px;line-height: 1.4"> +91 98762 98763 </strong></a> </td></tr><tr> <td align="center" class="" style="position:relative"> <a style="display:inline-block !important;border:0;" href="#"><img style=" width: 36px;display: inline-block !important;padding: 0 5px;" src="http://gizmosmart.com/demo/wp-content/uploads/2016/09/ic-fb.png" alt="Mail"/></a> <a style="display:inline-block !important;border:0;" href="#"><img style=" width: 36px;display: inline-block !important;padding: 0 5px;" src="http://gizmosmart.com/demo/wp-content/uploads/2016/09/ic-twitter.png" alt="Mail"/></a> <a style="display:inline-block !important;border:0;" href="#"><img style=" width: 36px;display: inline-block !important;padding: 0 5px;" src="http://gizmosmart.com/demo/wp-content/uploads/2016/09/ic-googleplus.png" alt="Mail"/></a> </td></tr><!-- <tr><td style="position:relative;"> <a href="http://gizmolife.in/"><img style="width:100%;height:auto;" src="http://gizmosmart.com/wp-content/uploads/2016/11/main.png"/> </a></td></tr>--> </table> </td></tr></table> </td></tr></table> </td></tr></table> </td></tr></table> </td></tr></table> </center> </body></html>';
			
		$params = array('to'	 => $email,
						'subject'=> 'User Account Deactivate',
						'html'   => $account_html,
						'text'   => 'test',
						'from'   => 'GizmoSmart<no-reply@kochartech.com>'
						);
			Helpers::sendMail($params);
		}        
          
					echo 1;
				}
				else{
					echo 2;
				}
				}
				else{

				echo 3;
			}
				
			}
			

        }
    }
	
	public function updateProfileView($id){
			$user_data =Session::get('user_data');
			if($user_data->user_type == 3)
			{
				$data= DB::table('bussiness as b')
				->select('b.bussiness_id','b.bussiness_name','b.phone','b.email','b.address','b.location','b.zipcode','b.description','b.website','b.companylogo','c.name as country','c.country_id','s.name as state','s.state_id','ci.name as city','ci.city_id','b.noofdevices','b.expirydate')
				->join('countries as c','c.country_id','=','b.countryid')
				->join('states as s','s.state_id','=','b.stateid') 
				->join('cities as ci','ci.city_id','=','b.cityid') 			
				->where('b.bussiness_id',$id)->get();
				$data =$data[0];
				$countries = $this->getCountry();
				$states = $this->getStatebyCountryData($data->country_id);
				$cities = $this->getCityByStateData($data->state_id);
				$pincodes = $this->getPinCodesByCityData($data->city_id);
				return view('admin.update_profile_view', compact('countries','states','cities', 'data', 'pincodes'));
			}
			
			if($user_data->user_type == 2)
			{
				$data=DB::table('customers as cust')
				->select('cust.first_name','cust.mobile','cust.email','cust.address','cust.pincode_id','c.name as country','s.name as state','ci.name as city','r.roleName as role_name','cust.country_id','cust.city_id','cust.state_id','u.role_id','cust.business_id', 'cust.child_of', 'cust.cust_id')
				->join('users as u', 'u.user_name','=', 'cust.email')
				->join('roles as r', 'r.id','=', 'u.role_id')
				->join('countries as c','c.country_id','=','cust.country_id')
				->join('states as s','s.state_id','=','cust.state_id') 
				->join('cities as ci','ci.city_id','=','cust.city_id') 
				->join('pincodes as pin', 'pin.pincode_id','=','cust.pincode_id')
				->where('u.user_type','=',2)
				->where('cust.email',$user_data->user_name)->get();
				$data =$data[0];
				$countries = $this->getCountry();
				$states = $this->getStatebyCountryData($data->country_id);
				$cities = $this->getCityByStateData($data->state_id);
				$pincodes = $this->getPinCodesByCityData($data->city_id);
				return view('admin.update_profile_view', compact('countries','states','cities', 'data', 'pincodes'));
			}
			 
		
		
	}
	
	/*update profie*/
	public function updateProfile(Request $request){
		$user_data =Session::get('user_data');
		if($user_data->user_type == 3)
		{
			$this->validate($request, [
			 'bussiness_name' => 'required|max:100',
			 'phone' => 'required|numeric|regex:/[0-9]{10}/',
			 'address' => 'required|max:255',
			 'country' => 'required',
			 'state' => 'required',
			 'city' => 'required',
			 'description' => 'required|max:255',
			 'website' => 'required|url',
			 'expirydate' => 'required',
			 'noofdevices' => 'required|numeric',
			 'zipcode'=>'required',
			 'companylogo' => 'max:2048|mimes:jpeg,png,jpg,gif,svg',
			]); 
        
			$business = array();
			$business['bussiness_name'] = $request['bussiness_name'];
			$business['phone'] = $request['phone'];
			$business['address']= $request['address'];
			$business['countryid']   = $request['country'];
			$business['stateid']  = $request['state'];
			$business['cityid']   = $request['city'];
			$business['description']   = $request['description'];
			$business['website']   = $request['website'];
			$business['expirydate']  = $request['expirydate'];
			$business['noofdevices']   = $request['noofdevices'];
			$business['zipcode']   = $request['zipcode'];
			
			$customer = array();
			$customer['first_name'] = $request['bussiness_name'];
			$customer['mobile'] = $request['phone'];
			$customer['modified_date'] = date('Y-m-d h:i:s');
			$customer['address'] = $request['address'];
			$customer['country_id'] = $request['country'];
			$customer['state_id'] = $request['state'];
			$customer['city_id'] = $request['city'];
			$customer['pincode_id'] = $request['zipcode'];
			
			//upload Company logo
			$image = $request->file('companylogo'); 
			if(!empty($image))
			{
				
				$imageName = time().'.'.$image->getClientOriginalExtension();
				$image->move(
					base_path() . '/asset/company_logo/', $imageName
				);
				$business['companylogo'] =$imageName;
			}
			else{
				$imageName = DB::table('bussiness')->select('companylogo')->where('bussiness_id', '=', $request['bussiness_id'])->get();
				$business['companylogo'] =$imageName[0]->companylogo;
			}
			if(!empty($user_data->user_name))
			{
				$updatebussiness = DB::table('bussiness')
				->where('email','=', $user_data->user_name)
				->update($business);
				
				$updateCustomer = DB::table('customers')
				->where('email','=', $user_data->user_name)
				->update($customer);
				if($updatebussiness==1)
				{
					Session::set('NAME', $request['bussiness_name']);
					Session::flash('msg','Profile updated successfully.');
					return Redirect::to('admin/profile');
				}
			}

		}
		elseif($user_data->user_type == 2)
		{
			
			$this->validate($request, [
			 'bussiness_name' => 'required|max:100',
			 'phone' => 'required|numeric|regex:/[0-9]{10}/',
			 'address' => 'required|max:255',
			 'country' => 'required',
			 'state' => 'required',
			 'city' => 'required',
			 'zipcode'=>'required',
			]); 
        
			$customers = array();
			$customers['first_name'] = $request['bussiness_name'];
			$customers['mobile'] = $request['phone'];
			$customers['address']= $request['address'];
			$customers['country_id']   = $request['country'];
			$customers['state_id']  = $request['state'];
			$customers['city_id']   = $request['city'];
			$customers['pincode_id']   = $request['zipcode'];
			
			if(!empty($user_data->user_name))
			{
				
				$updatecustomers = DB::table('customers')
				->where('email','=', $user_data->user_name)
				->update($customers);
				if($updatecustomers==1)
				{
					Session::set('NAME', $request['bussiness_name']);
					Session::flash('msg','Profile updated successfully.');
					return Redirect::to('admin/profile');
				}
			}

		}
		
	}
	
	/*get state by country Id*/
	public function getStatebyCountry(Request $request)
	{	
		$countryID = $request->country_id;
		$state_option = '';
		$states = DB::table('states')->select('state_id', 'name')->where('country_id','=', $countryID)->get();
		$state_option = '<option value="">--select--</option>';
		if(count($states))
		{
			foreach($states as $state)
			{
				$state_option .="<option value='". $state->state_id ."'>". $state->name ."</option>";
			}
		}
		echo $state_option;
		
	}
	
	public function getStatebyCountryData($countryID){
		$states = DB::table('states')->select('state_id', 'name')->where('country_id','=', $countryID)->get();
		return $states;
	}
	/*get city by State Id*/
	public function getCityByState(Request $request){
		$state_id = $request->state_id;
		$cities_option = '';
		$cities = DB::table('cities')->select('city_id', 'name')->where('state_id','=', $state_id)->get();
		$cities_option = '<option value="">--select--</option>';
		if(count($cities))
		{
			foreach($cities as $city)
			{
				$cities_option .="<option value='". $city->city_id ."'>". $city->name ."</option>";
			}
		}
		echo $cities_option;
	}
	
	public function getCityByStateData($state_id)
	{
		$cities = DB::table('cities')->select('city_id', 'name')->where('state_id','=', $state_id)->get();
		return $cities;
	}
	
	public function getPinCodesByCity(Request $request)
	{
		$city_id = $request->city_id;
		$pincodes_option = '';
		$pincodes = DB::table('pincodes')->select('pincode_id', 'pincode')->where('city_id','=', $city_id)->get();
		$pincodes_option = '<option value="">--select--</option>';
		if(count($pincodes))
		{
			foreach($pincodes as $pincode)
			{
				$pincodes_option .="<option value='". $pincode->pincode_id ."'>". $pincode->pincode ."</option>";
			}
		} 
		echo $pincodes_option; 
	}
	
	public function getPinCodesByCityData($city_id)
	{
		$pincodes = DB::table('pincodes')->select('pincode_id', 'pincode')->where('city_id','=', $city_id)->get();
		return $pincodes;
	}
	/*	Create Random Password */
	public function random_password( $length = 8 ) {
		$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_";
		$password = substr( str_shuffle( $chars ), 0, $length );
		return $password;
	}
	
	function rip_tags($string) {
   
    // ----- remove HTML TAGs -----
    $string = preg_replace ('/<[^>]*>/', ' ', $string);
   
    // ----- remove control characters -----
    $string = str_replace("\r", '', $string);    // --- replace with empty space
    $string = str_replace("\n", ' ', $string);   // --- replace with space
    $string = str_replace("\t", ' ', $string);   // --- replace with space
   
    // ----- remove multiple spaces -----
    $string = trim(preg_replace('/ {2,}/', ' ', $string));
   
    return $string;

	}
	
	public function reportingTo($cust_id = null){
		$user_data =Session::get('user_data');
		$reportingTo = '';
		if($user_data->user_type==2)//new change
		{
			$reportingTo = DB::table('customers as cust')
			->select('cust.cust_id','cust.first_name', 'r.roleName')
			->join('users as u', 'u.user_name','=', 'cust.email')
			->join('roles as r', 'r.id','=', 'u.role_id')
			->where('cust.status', true)
			->whereNotIn('u.role_id', ['1','3', '4'])
			->where('u.business_id', $user_data->business_id)
			->orderBy('r.id', 'desc')
			->get();
		}
		if($user_data->user_type==3)
		{
			$reportingTo = DB::table('customers as cust')
			->select('cust.cust_id','cust.first_name', 'r.roleName')
			->join('users as u', 'u.user_name','=', 'cust.email')
			->join('roles as r', 'r.id','=', 'u.role_id')
			->where('cust.status', true)
			->whereNotIn('u.role_id', ['1','3', '4'])
			->where('u.business_id', $user_data->business_id)
			->orderBy('r.id', 'desc')
			->get();
		}
		if($user_data->user_type==1)
		{
			
			//$business_id = DB::table('customers')->select('business_id')->where('cust_id', $cust_id)->get();
			//$business_id = $business_id[0]->business_id;
			$reportingTo = DB::table('customers as cust')
			->select('cust.cust_id','cust.first_name', 'r.roleName')
			->join('users as u', 'u.user_name','=', 'cust.email')
			->join('roles as r', 'r.id','=', 'u.role_id')
			->where('cust.status', true)
			->whereNotIn('u.role_id', ['1','3', '4'])
			//->where('u.business_id', $business_id)
			->orderBy('r.id', 'desc')
			->get();
		}
		return $reportingTo;
	}
	
	public function reportingToAjax(Request $request){
			if(!empty($request->business_id))
			{
				$reportingTo = DB::table('customers as cust')
				->select('cust.cust_id','cust.first_name', 'r.roleName')
				->join('users as u', 'u.user_name','=', 'cust.email')
				->join('roles as r', 'r.id','=', 'u.role_id')
				->where('cust.status', true)
				->whereNotIn('u.role_id', ['1','3', '4'])
				->where('u.user_name','<>',$request->userEmail)
				->where('u.business_id', $request->business_id)
				->orderBy('r.id', 'desc')
				->get();
				$reportingTo_option = '<option value="0">--select--</option>';
				if(count($reportingTo))
				{	$selected='';
					foreach($reportingTo as $reporting)
					{
						
						if($reporting->cust_id==$request->hid_reporting_to)
						{
							$selected ='selected';
						}
						else{
							$selected ='';
						} 
						$reportingTo_option .="<option value='". $reporting->cust_id ."' ".$selected.">". $reporting->first_name .'('.$reporting->roleName .')'."</option>";
					}
				}
				echo $reportingTo_option; 
			}
			
			
	}

}
