<?php
if (!isset($loop)) {
    $loop = React\EventLoop\Loop::get();
}

if (!isset($httpURI)) {
    $httpURI = '0.0.0.0:' . HTTP_PORT;
}

$httpSocket = new React\Socket\SocketServer($httpURI, [], $loop);

$server = new React\Http\HttpServer($loop, function (Psr\Http\Message\ServerRequestInterface $request) {
    $params = $request->getQueryParams();
    if (!isset($params['id']) || !isset($params['msg'])) {
        $body = "";
        $body .= '<!DOCTYPE html>';
        $body .= '<html lang="en">';
        $body .= '<head>';
        $body .= '    <title>Demo :: He thong dieu khien giam sat den chieu sang</title>';
        $body .= '    <meta charset="utf-8">';
        $body .= '    <meta name="viewport" content="width=device-width, initial-scale=1">';
        $body .= '    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">';
        $body .= '    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">';
        $body .= '    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>';
        $body .= '    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>';
        $body .= '    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.min.js"></script>';
        $body .= '    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/2.1.4/toastr.min.js"></script>';
        $body .= '    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/2.1.4/toastr.min.css">';
        $body .= '</head>';
        $body .= '<body>';
        $body .= '<div class="container">';
        $body .= '<table class="table">';
        $body .= '<thead><tr><th>Device ID</th><th>Device Name</th><th>TCP/IP</th><th>Action</th></tr></thead><tbody>';
        foreach($GLOBALS[DS_CONNECT] as $deviceId => $deviceInfo) {
            if (empty($deviceInfo[DEVICE_CONNECT]->getRemoteAddress())) {
                unset($GLOBALS[DS_CONNECT][$deviceId]);
                continue;
            }
            $safeDeviceId = htmlspecialchars((string) $deviceId, ENT_QUOTES, 'UTF-8');
            $safeDeviceName = htmlspecialchars((string) $deviceInfo[DEVICE_NAME], ENT_QUOTES, 'UTF-8');
            $safeRemoteAddress = htmlspecialchars((string) $deviceInfo[DEVICE_CONNECT]->getRemoteAddress(), ENT_QUOTES, 'UTF-8');
            $body .= "<tr>";
            $body .= "<td>$safeDeviceId</td>";
            $body .= "<td>$safeDeviceName</td>";
            $body .= "<td>$safeRemoteAddress</td>";
            $body .= '<td><form class="form-inline" method="get" action="/tcp/"> <input type="hidden" name="id" value="' . $safeDeviceId . '" /> ';
            $body .= '<div class="form-group"> <label for="msg">Send msg to [' . $safeDeviceName . ']:</label> <input type="text" class="form-control" name="msg" id="msg"> </div> ';
            $body .= '<button type="submit" class="btn btn-success">Send</button> </form></td>';
            $body .= "</tr>";

        }
        $body .= "</tbody></table>";
        $body .= '</div>';
        $body .= '</body>';
        $body .= '</html>';
        return new React\Http\Message\Response(
            200,
            array(
                'Content-Type' => 'text/html'
            ),
            $body
        );
    }

    $deviceId = $params['id'];
    $msg = $params['msg'];
    if (!isset($GLOBALS[DS_CONNECT][$deviceId])) {
        return new React\Http\Message\Response(
            200,
            array(
                'Content-Type' => 'text/plain'
            ),
            "Device $deviceId not found"
        );
    }
    $deviceInfo = $GLOBALS[DS_CONNECT][$deviceId];
    $deviceInfo[DEVICE_CONNECT]->write("$msg\n");
    return new React\Http\Message\Response(
        200,
        array(
            'Content-Type' => 'text/plain'
        ),
        "Send data success to $deviceId with msg $msg"
    );

});
$server->listen($httpSocket);

echo "\nhttp server at http://$httpURI";
