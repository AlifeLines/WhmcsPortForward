<?php
use Workerman\Worker;
require_once __DIR__ . '/vendor/autoload.php';
$service_worker = new Worker();
$service_worker->name = 'Forword Serice Main';
$service_worker->reloadable = false;
if(!@$GlobalProxyService){
	$GlobalProxyService = array();
}
$service_worker->onWorkerStart = function(){
	global $GlobalProxyService;
    foreach(glob(__DIR__.'/forward_service/*.php') as $start_filet){
	   echo 'Debug: '.$start_filet.' Will Be load'.PHP_EOL;
	   require $start_filet;
	   echo 'Debug: '.$start_filet.' load done'.PHP_EOL;
    }
};
$service_worker->onWorkerReload = function($worker){
	global $GlobalProxyService;
	$autoreload_tmp_file_info_main = json_decode(@file_get_contents(__DIR__.'/autoreload.tmp'),true);
    if(@$autoreload_tmp_file_info_main['reloadfile']){
	    $reloadfile = $autoreload_tmp_file_info_main['reloadfile'];
		foreach($reloadfile as $_reloadfile){
			$_reloadfile_name = pathinfo($_reloadfile, PATHINFO_BASENAME);
			$_reloadfile_name = @(explode('.',$_reloadfile_name))[0];
			if(@$GlobalProxyService[$_reloadfile_name]){
				echo 'Debug:Service '.$_reloadfile_name.' Will be stop'.PHP_EOL;
				echo 'Debug:Service '.$_reloadfile_name.' Run Worker TC Stop Commond....'.PHP_EOL;
				foreach(($GlobalProxyService[$_reloadfile_name]['tcstop']) as $TcstopOne){
					echo 'Debug:Service '.$_reloadfile_name.' Run Worker TC Stop Commond:['.$TcstopOne.']....'.PHP_EOL;
	                exec($TcstopOne);
                }
				echo 'Debug:Service '.$_reloadfile_name.' Run Worker Stop Commond....'.PHP_EOL;	
				($GlobalProxyService[$_reloadfile_name]['worker'])->stop();
				echo 'Debug:Service '.$_reloadfile_name.' Will be Unset....'.PHP_EOL;	
				unset($GlobalProxyService[$_reloadfile_name]);
			}
			if(file_exists($_reloadfile)){
				echo 'Debug:'.$_reloadfile.' Will be Reinclude....'.PHP_EOL;
				require $_reloadfile;
				echo 'Debug:'.$_reloadfile.' load done....'.PHP_EOL;
			}
		}
    }
	@unlink(__DIR__.'/autoreload.tmp');
};