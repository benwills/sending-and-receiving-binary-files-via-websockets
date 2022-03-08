<?php
use Swoole\WebSocket\Server;
use Swoole\Http\Request;
use Swoole\WebSocket\Frame;

////////////////////////////////////////////////////////////////////////////////
$server = new Server("127.0.0.1", 8888);
$server->set([
	//
	// important for allowing larger files to be uploaded:
	//
	'package_max_length' => 8 * 1024 * 1024,
]);

//--------------------------------------------------------------
$server->on("Start", function(Server $server)
{
	echo "Swoole WebSocket Server is started at wss://127.0.0.1:9502\n";
});

//--------------------------------------------------------------
$server->on('Open', function(Server $server, Swoole\Http\Request $request)
{
	echo "connection open: {$request->fd}\n";
	print_r($server->getClientInfo($request->fd));
});

//--------------------------------------------------------------
$server->on('Message', function(Server $server, Frame $frame)
{
	if ('!' == $frame->data[0]) {
		// we're only testing binary uploads here

		list($jsonMsg, $fileData) = explode("\r\n\r\n", $frame->data, 2);
		$jsonMsg = substr($jsonMsg, 1); // trim '!' here vs on the larger file
		$jsonMsg = json_decode($jsonMsg);

		echo "\n\n";
		print_r("frame length: ".strlen($frame->data));
		print_r(array_keys(get_object_vars($frame)));
		print_r($jsonMsg);

		if ($jsonMsg->size === strlen($fileData)) {
			file_put_contents(__DIR__.'/uploads/'.$jsonMsg->name, $fileData);
		}
		else {
			echo "\nfile byte length reported from browser unequal to data received\n";
		}
	}
	else {
		// ...handle your other message types
	}
});

//--------------------------------------------------------------
$server->on('Close', function(Server $server, int $fd)
{
	echo "connection close: {$fd}\n";
});

//--------------------------------------------------------------
$server->on('Disconnect', function(Server $server, int $fd)
{
	echo "connection disconnect: {$fd}\n";
});

//--------------------------------------------------------------
$server->start();
