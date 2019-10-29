<?php
use \Workerman\Worker;
use \Workerman\Connection\AsyncTcpConnection;
use \Workerman\Lib\Timer;
require_once __DIR__ . '/../vendor/autoload.php';
include __DIR__.'/../config.php';
$GlobalProxyService[[SERID]]['maxconnnum'] = [MAXCONNNUM];
$GlobalProxyService[[SERID]]['tcstart'] = json_decode('[TCSTART]',true);
$GlobalProxyService[[SERID]]['tcstop'] = json_decode('[TCSTOP]',true);
$GlobalProxyService[[SERID]]['client'] = new Predis\Client(['scheme' => 'tcp','host' => $RedisIP,'port' => $RedisPort,'parameters'=>['password' => $RedisPass]]);
($GlobalProxyService[[SERID]]['client'])->set('[SERID]_maxconnnum',0);
if('[PTYPE]' == 'udp'){
    $GlobalProxyService[[SERID]]['raddress'] = 'udp://[RSIP]:[RPORT]';
}else{
    $GlobalProxyService[[SERID]]['raddress'] = 'tcp://[RSIP]:[RPORT]';
}
if('[PTYPE]' == 'udp'){
    $GlobalProxyService[[SERID]]['worker'] = new Worker('udp://0.0.0.0:[SPORT]');
}else{
    $GlobalProxyService[[SERID]]['worker'] = new Worker('tcp://0.0.0.0:[SPORT]');
}
($GlobalProxyService[[SERID]]['worker'])->name = 'Service [SERID] Forward Worker';
if('[PTYPE]' == 'udp'){
($GlobalProxyService[[SERID]]['worker'])->onConnect = function($connection)use($GlobalProxyService){
	if((($GlobalProxyService[[SERID]]['client'])->get('[SERID]_maxconnnum'))+1 > $GlobalProxyService[[SERID]]['maxconnnum']){
		$connection->close();
	}
	($GlobalProxyService[[SERID]]['client'])->incr('[SERID]_maxconnnum');
	$connection_to_r = new AsyncUdpConnection($GlobalProxyService[[SERID]]['raddress']);
    $connection_to_r->onMessage = function($connection_to_r, $buffer)use($connection,$GlobalProxyService){
		($GlobalProxyService[[SERID]]['client'])->set('[SERID]_download',(($GlobalProxyService[[SERID]]['client'])->get('[SERID]_download'))+strlen($buffer));
        $connection->send($buffer);
    };
    $connection_to_r->onClose = function($connection_to_r)use($connection,$GlobalProxyService){
		if(@$connection_to_r->connnumdel){
			($GlobalProxyService[[SERID]]['client'])->decr('[SERID]_maxconnnum');
		}
		$connection->connnumdel = true;
        $connection->close();
    };
    $connection_to_r->connect();
    $connection->onMessage = function($connection, $buffer)use($connection_to_r,$GlobalProxyService){
		($GlobalProxyService[[SERID]]['client'])->set('[SERID]_upload',(($GlobalProxyService[[SERID]]['client'])->get('[SERID]_upload'))+strlen($buffer));
        $connection_to_r->send($buffer);
    };
    $connection->onClose = function($connection)use($connection_to_r,$GlobalProxyService){
		if(@$connection->connnumdel){
			($GlobalProxyService[[SERID]]['client'])->decr('[SERID]_maxconnnum');
		}
		$connection_to_r->connnumdel = true;
        $connection_to_r->close();
    };
	$connection->onError = function($connection)use($connection_to_r,$GlobalProxyService){
		if(@$connection->connnumdel){
			($GlobalProxyService[[SERID]]['client'])->decr('[SERID]_maxconnnum');
		}
		$connection_to_r->connnumdel = true;
        @$connection_to_r->close();
    };
};
}else{
($GlobalProxyService[[SERID]]['worker'])->onConnect = function($connection)use($GlobalProxyService){
	if((($GlobalProxyService[[SERID]]['client'])->get('[SERID]_maxconnnum'))+1 > $GlobalProxyService[[SERID]]['maxconnnum']){
		$connection->close();
	}
	($GlobalProxyService[[SERID]]['client'])->incr('[SERID]_maxconnnum');
	$connection_to_r = new AsyncTcpConnection($GlobalProxyService[[SERID]]['raddress']);
    $connection_to_r->onMessage = function($connection_to_r, $buffer)use($connection,$GlobalProxyService){
		($GlobalProxyService[[SERID]]['client'])->set('[SERID]_download',(($GlobalProxyService[[SERID]]['client'])->get('[SERID]_download'))+strlen($buffer));
        $connection->send($buffer);
    };
    $connection_to_r->onClose = function($connection_to_r)use($connection,$GlobalProxyService){
		if(@$connection_to_r->connnumdel){
			($GlobalProxyService[[SERID]]['client'])->decr('[SERID]_maxconnnum');
		}
		$connection->connnumdel = true;
        $connection->close();
    };
    $connection_to_r->onError = function($connection_to_r)use($connection,$GlobalProxyService){
		if(@$connection_to_r->connnumdel){
			($GlobalProxyService[[SERID]]['client'])->decr('[SERID]_maxconnnum');
		}
		$connection->connnumdel = true;
        $connection->close();
    };
    $connection_to_r->connect();
    $connection->onMessage = function($connection, $buffer)use($connection_to_r,$GlobalProxyService){
		($GlobalProxyService[[SERID]]['client'])->set('[SERID]_upload',(($GlobalProxyService[[SERID]]['client'])->get('[SERID]_upload'))+strlen($buffer));
        $connection_to_r->send($buffer);
    };
	$connection_to_r->onBufferFull = function($connection_to_r)use($connection){
        $connection->pauseRecv();
    };
    $connection->onBufferFull = function($connection)use($connection_to_r){
        $connection_to_r->pauseRecv();
    };
    $connection->onBufferDrain = function($connection)use($connection_to_r){
        $connection_to_r->resumeRecv();
    };
	$connection_to_r->onBufferDrain = function($connection_to_r)use($connection){
        $connection->resumeRecv();
    };
    $connection->onClose = function($connection)use($connection_to_r,$GlobalProxyService){
		if(@$connection->connnumdel){
			($GlobalProxyService[[SERID]]['client'])->decr('[SERID]_maxconnnum');
		}
		$connection_to_r->connnumdel = true;
        $connection_to_r->close();
    };
    $connection->onError = function($connection)use($connection_to_r,$GlobalProxyService){
		if(@$connection->connnumdel){
			($GlobalProxyService[[SERID]]['client'])->decr('[SERID]_maxconnnum');
		}
		$connection_to_r->connnumdel = true;
        $connection_to_r->close();
    };
};
}
foreach(($GlobalProxyService[[SERID]]['tcstop']) as $TcstopOne){
	exec($TcstopOne);
}
foreach(($GlobalProxyService[[SERID]]['tcstart']) as $TcstartOne){
	exec($TcstartOne);
}
($GlobalProxyService[[SERID]]['worker'])->listen();