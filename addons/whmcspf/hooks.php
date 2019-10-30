<?php
use \WHMCS\Database\Capsule as Capsule;
if(!function_exists('whmcspf_getParamsFromServiceID')){
function whmcspf_getParamsFromServiceID($servid,$uid = null,$field){
    $ownerRow = Capsule::table('tblhosting')->where('id',$servid)->first();
    if (!$ownerRow) {
        return false;
    }
    if (!is_null($uid) && $uid != $ownerRow->userid){
        return false;
    }
    $FieldRow = Capsule::table('tblcustomfields')->where( 'relid',$ownerRow->packageid)->where('fieldname',$field)->first();
    if (!$FieldRow) {
        return false;
    }
    $ValueRow = Capsule::table('tblcustomfieldsvalues')->where('fieldid', $FieldRow->id)->where('relid',$ownerRow->id)->first();
    if (!$ValueRow ) {
        return false;
    }
	return $ValueRow->value;
}
}
if(!function_exists('whmcspf_setCustomfieldsValue')){
function whmcspf_setCustomfieldsValue($field,$value,$servid,$uid){
    $ownerRow = Capsule::table('tblhosting')->where('id',$servid)->first();
    if (!$ownerRow){
         return false;
    }
    if (!is_null($uid) && $uid != $ownerRow->userid){
        return false;
    }
    $res = Capsule::table('tblcustomfields')->where('relid',$ownerRow->packageid)->where('fieldname',$field)->first();
    if ($res) {
        $fieldValue = Capsule::table('tblcustomfieldsvalues')->where('relid',$ownerRow->id)->where('fieldid',$res->id)->first();
        if ($fieldValue) {
            if($fieldValue->value != $value) {
                Capsule::table('tblcustomfieldsvalues')->where('relid',$ownerRow->id)->where('fieldid', $res->id)->update(['value' => $value,]);
            }else{
                Capsule::table('tblcustomfieldsvalues')->insert(['relid' => $ownerRow->id,'fieldid' => $res->id,'value' => $value]);
            }
        }else{
			Capsule::table('tblcustomfieldsvalues')->insert(['relid' => $ownerRow->id,'fieldid' => $res->id,'value' => $value]);
		}
    }
}
}
add_hook('DailyCronJob', 1, function($vars) {
    $todayunsusp = Capsule::table('mod_whmcspf_suspservice')->where('untime',date("Y-m-d"))->get();
	if($todayunsusp){
		foreach ( $todayunsusp as $listone){
			$DataArray = Capsule::table('tblhosting')->where('id',$listone->serviceid)->first();
			if($DataArray->domainstatus != 'Suspended' || $DataArray->suspendreason != '流量超额'){
				continue;
			}
			$laresults = localAPI('ModuleUnsuspend', array('serviceid' => $listone->serviceid), Capsule::table('tbladmins')->first()->id);
			if($laresults['result'] == 'success'){
				Capsule::table('tblhosting')->where('id',$listone->serviceid)->update(['domainstatus' => 'Active']);
				whmcspf_setCustomfieldsValue('forwardstatus','Active',$listone->serviceid,null);
				Capsule::table('mod_whmcspf_suspservice')->where('serviceid',$listone->serviceid)->delete();
			}
		}
	}
	if(date("d") == '1'){
		$ProductList = Capsule::table('tblproducts')->where('servertype','portforward')->get();
		$AdminID = Capsule::table('tbladmins')->first()->id;
		if(!$ProductList){
			return ;
		}
		foreach($ProductList as $ProductListOne){
			$ServiceList = Capsule::table('tblhosting')->where('domainstatus','Active')->where('packageid',$ProductListOne->id)->get();
			if(!$ServiceList){
				continue;
			}
			foreach($ServiceList as $ServiceListOne){
				$LastBWRESET = whmcspf_getParamsFromServiceID($ServiceListOne->id,null,'sysbwreset');
				if($LastBWRESET != date("Y-m")){
					$laresultst = localAPI('ModuleCustom',array('serviceid' => $ServiceListOne->id,'func_name' => 'resetbw'),$AdminID);
					if($laresultst['result'] == 'success'){
						whmcspf_setCustomfieldsValue('sysbwreset',date("Y-m"),$ServiceListOne->id,null);
					}
				}
			}
		}
	}
});
add_hook('AfterModuleTerminate', 1, function($vars) {
    Capsule::table('mod_whmcspf_suspservice')->where('serviceid',$vars['serviceid'])->delete();
});