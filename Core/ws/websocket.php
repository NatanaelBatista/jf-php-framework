<?php

/*
    Based on PHP WebSocket Server 0.2
     - http://code.google.com/p/php-websocket-server/
     - http://code.google.com/p/php-websocket-server/wiki/Scripting
    WebSocket Protocol 07
     - http://tools.ietf.org/html/draft-ietf-hybi-thewebsocketprotocol-07
     - Supported by Firefox 6 (30/08/2011)
    Whilst a big effort is made to follow the protocol documentation, the current script version may unknowingly differ.
    Please report any bugs you may find, all feedback and questions are welcome!
*/
class PHPWebSocket
{
    // maximum amount of clients that can be connected at one time
    const WS_MAX_CLIENTS = 100;
    // maximum amount of clients that can be connected at one time on the same IP v4 address
    const WS_MAX_CLIENTS_PER_IP = 15;
    // amount of seconds a client has to send data to the server, before a ping request is sent to the client,
    // if the client has not completed the opening handshake, the ping request is skipped and the client connection is closed
    const WS_TIMEOUT_RECV = 10;
    // amount of seconds a client has to reply to a ping request, before the client connection is closed
    const WS_TIMEOUT_PONG = 5;
    // the maximum length, in bytes, of a frame's payload data (a message consists of 1 or more frames), this is also internally limited to 2,147,479,538
    const WS_MAX_FRAME_PAYLOAD_RECV = 100000;
    // the maximum length, in bytes, of a message's payload data, this is also internally limited to 2,147,483,647
    const WS_MAX_MESSAGE_PAYLOAD_RECV = 500000;
    // internal
    const WS_FIN =  128;
    const WS_MASK = 128;
    const WS_OPCODE_CONTINUATION = 0;
    const WS_OPCODE_TEXT =         1;
    const WS_OPCODE_BINARY =       2;
    const WS_OPCODE_CLOSE =        8;
    const WS_OPCODE_PING =         9;
    const WS_OPCODE_PONG =         10;
    const WS_PAYLOAD_LENGTH_16 = 126;
    const WS_PAYLOAD_LENGTH_63 = 127;
    const WS_READY_STATE_CONNECTING = 0;
    const WS_READY_STATE_OPEN =       1;
    const WS_READY_STATE_CLOSING =    2;
    const WS_READY_STATE_CLOSED =     3;
    const WS_STATUS_NORMAL_CLOSE =             1000;
    const WS_STATUS_GONE_AWAY =                1001;
    const WS_STATUS_PROTOCOL_ERROR =           1002;
    const WS_STATUS_UNSUPPORTED_MESSAGE_TYPE = 1003;
    const WS_STATUS_MESSAGE_TOO_BIG =          1004;
    const WS_STATUS_TIMEOUT = 3000;
    // global vars
    public $clients       = array();
    public $clients        = array();
    public $clients_count   = 0;
    public $clients_per_ip = array();
    public $wsOnEvents      = array();
    /*
        $this->clients[ integer ClientID ] = array(
            0 => resource  Socket,                            // client socket
            1 => string    MessageBuffer,                     // a blank string when there's no incoming frames
            2 => integer   ReadyState,                        // between 0 and 3
            3 => integer   LastRecvTime,                      // set to time() when the client is added
            4 => int/false PingSentTime,                      // false when the server is not waiting for a pong
            5 => int/false CloseStatus,                       // close status that wsOnClose() will be called with
            6 => integer   IPv4,                              // client's IP stored as a signed long, retrieved from ip2long()
            7 => int/false FramePayloadDataLength,            // length of a frame's payload data, reset to false when all frame data has been read (cannot reset to 0, to allow reading of mask key)
            8 => integer   FrameBytesRead,                    // amount of bytes read for a frame, reset to 0 when all frame data has been read
            9 => string    FrameBuffer,                       // joined onto end as a frame's data comes in, reset to blank string when all frame data has been read
            10 => integer  MessageOpcode,                     // stored by the first frame for fragmented messages, default value is 0
            11 => integer  MessageBufferLength                // the payload data length of MessageBuffer
        )
        $clients[ integer ClientID ] = resource Socket         // this one-dimensional array is used for socket_select()
                                                              // $clients[ 0 ] is the socket listening for incoming client connections
        $clients_count = integer ClientCount                  // amount of clients currently connected
        $clients_per_ip[ integer IP ] = integer ClientCount  // amount of clients connected per IP v4 address
    */
    // server state functions
    public static $server      = null;

    public static function run()
    {
        if ( static::$server ) {
            return false;
        }
        if ( !$server = static::init() ) {
            return false;
        }
        $server->loop();

    }

    protected function loop()
    {
        $write              = array();
        $except             = array();
        $next_ping_check    = time() + 1;
        
        while ( $this->sock ) {
            $changed    = $this->clients;
            $result     = socket_select( $this->clients, $write, $except, 1 );
            
            if ( !$result ) {
                socket_close( $this->sock );
                return false;
            }

            $this->listenForNewClient();
            $this->checkClientsConnection( $next_ping_check );
            
            if (time() >= $next_ping_check) {
                $this->wsCheckIdleClients();
                $next_ping_check = time() + 1;
            }
        }
        return true; // returned when wsStopServer() is called
    }

    protected function init()
    {
        $server                     = new static();
        $server->hostname           = Config::get( 'websocket.hostname' );
        $server->port               = Config::get( 'websocket.port' );
        $server->max_clients        = Config::get( 'websocket.max_clients' );
        $server->max_clients_per_ip = Config::get( 'websocket.max_clientes_per_ip' );

        if ( !$server->sock = socket_create( AF_INET, SOCK_STREAM, SOL_TCP ) ) {
            return false;
        }
        if ( !socket_set_option( $sock, SOL_SOCKET, SO_REUSEADDR, 1 ) ) {
            socket_close( $sock );
            return false;
        }
        if (!socket_bind( $sock, $host, $port)) {
            socket_close( $sock );
            return false;
        }
        if (!socket_listen( $sock, 10)) {
            socket_close( $sock );
            return false;
        }
        static::$server     = $server;
        return $server;
    }

    protected function listenForNewClient()
    {
        $client = socket_accept( $this->sock );
        if ( !$client ) {
            return;
        }

        // fetch client IP as integer
        $client_ip                  = '';
        $result                     = socket_getpeername( $client, $client_ip );
        $client_ip                  = ip2long( $client_ip );
        $max_clients_reached        = $this->clients_count >= $this->max_clients;
        $has_client_ip              = isset( $this->clients_per_ip[ $client_ip ] );
        $ips_of_active_client       = $has_client_ip
            ? $this->clients_per_ip[ $client_ip ]
            : 0;
        $max_clients_per_ip_reached = $ips_of_active_client >= $this->max_clients_per_ip;
        
        if ( $result && !$max_clients_reached && !$max_clients_per_ip_reached ) {
            $this->wsAddClient($client, $client_ip);
            continue;
        }
        socket_close($client);
    }

    protected function checkClientsConnection()
    {
        foreach ( $this->clients as $client_id => $socket ) {
            // client socket changed
            $buffer = '';
            $bytes = @socket_recv( $socket, $buffer, 4096, 0 );
            
            if ($bytes === false) {
                // error on recv, remove client socket (will check to send close frame)
                $this->wsSendClientClose($client_id, self::WS_STATUS_PROTOCOL_ERROR);
                continue;
            }
            if ($bytes > 0) {
                // process handshake or frame(s)
                if (!$this->wsProcessClient($client_id, $buffer, $bytes)) {
                    $this->wsSendClientClose($client_id, self::WS_STATUS_PROTOCOL_ERROR);
                }
                continue;
            }
            // 0 bytes received from client, meaning the client closed the TCP connection
            $this->wsRemoveClient($client_id);
        }
    }

    protected function wsStopServer() {
        // check if server is not running
        if ( empty( $this->sock ) ) {
            return false;
        }

        // close all client connections
        foreach ( $this->clients as $clientID => $client) {
            // if the client's opening handshake is complete, tell the client the server is 'going away'
            if ($client[2] != self::WS_READY_STATE_CONNECTING) {
                $this->wsSendClientClose($clientID, self::WS_STATUS_GONE_AWAY);
            }
            socket_close( $client[0] );
        }
        // close the socket which listens for incoming clients
        socket_close( $this->sock );
        // reset variables
        $this->clients          = array();
        $this->clients       = array();
        $this->clients_count   = 0;
        $this->clients_per_ip = array();
        return true;
    }
    // client timeout functions
    function wsCheckIdleClients() {
        $time = time();
        foreach ($this->clients as $clientID => $client) {
            if ($client[2] == self::WS_READY_STATE_CLOSED) {
                continue;
            }

            // client ready state is not closed
            if ( $client[4] !== false ) {
                // ping request has already been sent to client, pending a pong reply
                if ($time >= $client[4] + self::WS_TIMEOUT_PONG) {
                    // client didn't respond to the server's ping request in self::WS_TIMEOUT_PONG seconds
                    $this->wsSendClientClose($clientID, self::WS_STATUS_TIMEOUT);
                    $this->wsRemoveClient($clientID);
                }
                continue;
            }

            // last data was received >= self::WS_TIMEOUT_RECV seconds ago
            if ($client[2] != self::WS_READY_STATE_CONNECTING) {
                // client ready state is open or closing
                $this->clients[$clientID][4] = time();
                $this->wsSendClientMessage($clientID, self::WS_OPCODE_PING, '');
                continue;
            }

            // client ready state is connecting
            $this->wsRemoveClient($clientID);
        }
    }
    // client existence functions
    function wsAddClient($socket, $client_ip) {
        // increase amount of clients connected
        $this->clients_count++;
        // increase amount of clients connected on this client's IP
        if (isset($this->clients_per_ip[$client_ip])) {
            $this->clients_per_ip[$client_ip]++;
        }
        else {
            $this->clients_per_ip[$client_ip] = 1;
        }
        // fetch next client ID
        $clientID = $this->wsGetNextClientID();
        // store initial client data
        $this->clients[$clientID] = array($socket, '', self::WS_READY_STATE_CONNECTING, time(), false, 0, $client_ip, false, 0, '', 0, 0);
        // store socket - used for socket_select()
        $this->clients[$clientID] = $socket;
    }
    function wsRemoveClient($clientID) {
        // fetch close status (which could be false), and call wsOnClose
        $closeStatus = $this->clients[$clientID][5];
        if ( array_key_exists('close', $this->wsOnEvents) )
            foreach ( $this->wsOnEvents['close'] as $func )
                $func($clientID, $closeStatus);
        // close socket
        $socket = $this->clients[$clientID][0];
        socket_close($socket);
        // decrease amount of clients connected on this client's IP
        $client_ip = $this->clients[$clientID][6];
        if ($this->clients_per_ip[$client_ip] > 1) {
            $this->clients_per_ip[$client_ip]--;
        }
        else {
            unset($this->clients_per_ip[$client_ip]);
        }
        // decrease amount of clients connected
        $this->clients_count--;
        // remove socket and client data from arrays
        unset($this->clients[$clientID], $this->clients[$clientID]);
    }
    // client data functions
    function wsGetNextClientID() {
        $i = 1; // starts at 1 because 0 is the listen socket
        while (isset($this->clients[$i])) $i++;
        return $i;
    }
    function wsGetClientSocket($clientID) {
        return $this->clients[$clientID][0];
    }
    // client read functions
    function wsProcessClient($clientID, &$buffer, $bufferLength) {
        if ($this->clients[$clientID][2] == self::WS_READY_STATE_OPEN) {
            // handshake completed
            $result = $this->wsBuildClientFrame($clientID, $buffer, $bufferLength);
        }
        elseif ($this->clients[$clientID][2] == self::WS_READY_STATE_CONNECTING) {
            // handshake not completed
            $result = $this->wsProcessClientHandshake($clientID, $buffer);
            if ($result) {
                $this->clients[$clientID][2] = self::WS_READY_STATE_OPEN;
                if ( array_key_exists('open', $this->wsOnEvents) )
                    foreach ( $this->wsOnEvents['open'] as $func )
                        $func($clientID);
            }
        }
        else {
            // ready state is set to closed
            $result = false;
        }
        return $result;
    }
    function wsBuildClientFrame($clientID, &$buffer, $bufferLength) {
        // increase number of bytes read for the frame, and join buffer onto end of the frame buffer
        $this->clients[$clientID][8] += $bufferLength;
        $this->clients[$clientID][9] .= $buffer;
        // check if the length of the frame's payload data has been fetched, if not then attempt to fetch it from the frame buffer
        if ($this->clients[$clientID][7] !== false || $this->wsCheckSizeClientFrame($clientID) == true) {
            // work out the header length of the frame
            $headerLength = ($this->clients[$clientID][7] <= 125 ? 0 : ($this->clients[$clientID][7] <= 65535 ? 2 : 8)) + 6;
            // check if all bytes have been received for the frame
            $frameLength = $this->clients[$clientID][7] + $headerLength;
            if ($this->clients[$clientID][8] >= $frameLength) {
                // check if too many bytes have been read for the frame (they are part of the next frame)
                $nextFrameBytesLength = $this->clients[$clientID][8] - $frameLength;
                if ($nextFrameBytesLength > 0) {
                    $this->clients[$clientID][8] -= $nextFrameBytesLength;
                    $nextFrameBytes = substr($this->clients[$clientID][9], $frameLength);
                    $this->clients[$clientID][9] = substr($this->clients[$clientID][9], 0, $frameLength);
                }
                // process the frame
                $result = $this->wsProcessClientFrame($clientID);
                // check if the client wasn't removed, then reset frame data
                if (isset($this->clients[$clientID])) {
                    $this->clients[$clientID][7] = false;
                    $this->clients[$clientID][8] = 0;
                    $this->clients[$clientID][9] = '';
                }
                // if there's no extra bytes for the next frame, or processing the frame failed, return the result of processing the frame
                if ($nextFrameBytesLength <= 0 || !$result) return $result;
                // build the next frame with the extra bytes
                return $this->wsBuildClientFrame($clientID, $nextFrameBytes, $nextFrameBytesLength);
            }
        }
        return true;
    }
    function wsCheckSizeClientFrame($clientID) {
        // check if at least 2 bytes have been stored in the frame buffer
        if ($this->clients[$clientID][8] > 1) {
            // fetch payload length in byte 2, max will be 127
            $payloadLength = ord(substr($this->clients[$clientID][9], 1, 1)) & 127;
            if ($payloadLength <= 125) {
                // actual payload length is <= 125
                $this->clients[$clientID][7] = $payloadLength;
            }
            elseif ($payloadLength == 126) {
                // actual payload length is <= 65,535
                if (substr($this->clients[$clientID][9], 3, 1) !== false) {
                    // at least another 2 bytes are set
                    $payloadLengthExtended = substr($this->clients[$clientID][9], 2, 2);
                    $array = unpack('na', $payloadLengthExtended);
                    $this->clients[$clientID][7] = $array['a'];
                }
            }
            else {
                // actual payload length is > 65,535
                if (substr($this->clients[$clientID][9], 9, 1) !== false) {
                    // at least another 8 bytes are set
                    $payloadLengthExtended = substr($this->clients[$clientID][9], 2, 8);
                    // check if the frame's payload data length exceeds 2,147,483,647 (31 bits)
                    // the maximum integer in PHP is "usually" this number. More info: http://php.net/manual/en/language.types.integer.php
                    $payloadLengthExtended32_1 = substr($payloadLengthExtended, 0, 4);
                    $array = unpack('Na', $payloadLengthExtended32_1);
                    if ($array['a'] != 0 || ord(substr($payloadLengthExtended, 4, 1)) & 128) {
                        $this->wsSendClientClose($clientID, self::WS_STATUS_MESSAGE_TOO_BIG);
                        return false;
                    }
                    // fetch length as 32 bit unsigned integer, not as 64 bit
                    $payloadLengthExtended32_2 = substr($payloadLengthExtended, 4, 4);
                    $array = unpack('Na', $payloadLengthExtended32_2);
                    // check if the payload data length exceeds 2,147,479,538 (2,147,483,647 - 14 - 4095)
                    // 14 for header size, 4095 for last recv() next frame bytes
                    if ($array['a'] > 2147479538) {
                        $this->wsSendClientClose($clientID, self::WS_STATUS_MESSAGE_TOO_BIG);
                        return false;
                    }
                    // store frame payload data length
                    $this->clients[$clientID][7] = $array['a'];
                }
            }
            // check if the frame's payload data length has now been stored
            if ($this->clients[$clientID][7] !== false) {
                // check if the frame's payload data length exceeds self::WS_MAX_FRAME_PAYLOAD_RECV
                if ($this->clients[$clientID][7] > self::WS_MAX_FRAME_PAYLOAD_RECV) {
                    $this->clients[$clientID][7] = false;
                    $this->wsSendClientClose($clientID, self::WS_STATUS_MESSAGE_TOO_BIG);
                    return false;
                }
                // check if the message's payload data length exceeds 2,147,483,647 or self::WS_MAX_MESSAGE_PAYLOAD_RECV
                // doesn't apply for control frames, where the payload data is not internally stored
                $controlFrame = (ord(substr($this->clients[$clientID][9], 0, 1)) & 8) == 8;
                if (!$controlFrame) {
                    $newMessagePayloadLength = $this->clients[$clientID][11] + $this->clients[$clientID][7];
                    if ($newMessagePayloadLength > self::WS_MAX_MESSAGE_PAYLOAD_RECV || $newMessagePayloadLength > 2147483647) {
                        $this->wsSendClientClose($clientID, self::WS_STATUS_MESSAGE_TOO_BIG);
                        return false;
                    }
                }
                return true;
            }
        }
        return false;
    }
    function wsProcessClientFrame($clientID) {
        // store the time that data was last received from the client
        $this->clients[$clientID][3] = time();
        // fetch frame buffer
        $buffer = &$this->clients[$clientID][9];
        // check at least 6 bytes are set (first 2 bytes and 4 bytes for the mask key)
        if (substr($buffer, 5, 1) === false) return false;
        // fetch first 2 bytes of header
        $octet0 = ord(substr($buffer, 0, 1));
        $octet1 = ord(substr($buffer, 1, 1));
        $fin = $octet0 & self::WS_FIN;
        $opcode = $octet0 & 15;
        $mask = $octet1 & self::WS_MASK;
        if (!$mask) return false; // close socket, as no mask bit was sent from the client
        // fetch byte position where the mask key starts
        $seek = $this->clients[$clientID][7] <= 125 ? 2 : ($this->clients[$clientID][7] <= 65535 ? 4 : 10);
        // read mask key
        $maskKey = substr($buffer, $seek, 4);
        $array = unpack('Na', $maskKey);
        $maskKey = $array['a'];
        $maskKey = array(
            $maskKey >> 24,
            ($maskKey >> 16) & 255,
            ($maskKey >> 8) & 255,
            $maskKey & 255
        );
        $seek += 4;
        // decode payload data
        if (substr($buffer, $seek, 1) !== false) {
            $data = str_split(substr($buffer, $seek));
            foreach ($data as $key => $byte) {
                $data[$key] = chr(ord($byte) ^ ($maskKey[$key % 4]));
            }
            $data = implode('', $data);
        }
        else {
            $data = '';
        }
        // check if this is not a continuation frame and if there is already data in the message buffer
        if ($opcode != self::WS_OPCODE_CONTINUATION && $this->clients[$clientID][11] > 0) {
            // clear the message buffer
            $this->clients[$clientID][11] = 0;
            $this->clients[$clientID][1] = '';
        }
        // check if the frame is marked as the final frame in the message
        if ($fin == self::WS_FIN) {
            // check if this is the first frame in the message
            if ($opcode != self::WS_OPCODE_CONTINUATION) {
                // process the message
                return $this->wsProcessClientMessage($clientID, $opcode, $data, $this->clients[$clientID][7]);
            }
            else {
                // increase message payload data length
                $this->clients[$clientID][11] += $this->clients[$clientID][7];
                // push frame payload data onto message buffer
                $this->clients[$clientID][1] .= $data;
                // process the message
                $result = $this->wsProcessClientMessage($clientID, $this->clients[$clientID][10], $this->clients[$clientID][1], $this->clients[$clientID][11]);
                // check if the client wasn't removed, then reset message buffer and message opcode
                if (isset($this->clients[$clientID])) {
                    $this->clients[$clientID][1] = '';
                    $this->clients[$clientID][10] = 0;
                    $this->clients[$clientID][11] = 0;
                }
                return $result;
            }
        }
        else {
            // check if the frame is a control frame, control frames cannot be fragmented
            if ($opcode & 8) return false;
            // increase message payload data length
            $this->clients[$clientID][11] += $this->clients[$clientID][7];
            // push frame payload data onto message buffer
            $this->clients[$clientID][1] .= $data;
            // if this is the first frame in the message, store the opcode
            if ($opcode != self::WS_OPCODE_CONTINUATION) {
                $this->clients[$clientID][10] = $opcode;
            }
        }
        return true;
    }
    function wsProcessClientMessage($clientID, $opcode, &$data, $dataLength) {
        // check opcodes
        if ($opcode == self::WS_OPCODE_PING) {
            // received ping message
            return $this->wsSendClientMessage($clientID, self::WS_OPCODE_PONG, $data);
        }
        elseif ($opcode == self::WS_OPCODE_PONG) {
            // received pong message (it's valid if the server did not send a ping request for this pong message)
            if ($this->clients[$clientID][4] !== false) {
                $this->clients[$clientID][4] = false;
            }
        }
        elseif ($opcode == self::WS_OPCODE_CLOSE) {
            // received close message
            if (substr($data, 1, 1) !== false) {
                $array = unpack('na', substr($data, 0, 2));
                $status = $array['a'];
            }
            else {
                $status = false;
            }
            if ($this->clients[$clientID][2] == self::WS_READY_STATE_CLOSING) {
                // the server already sent a close frame to the client, this is the client's close frame reply
                // (no need to send another close frame to the client)
                $this->clients[$clientID][2] = self::WS_READY_STATE_CLOSED;
            }
            else {
                // the server has not already sent a close frame to the client, send one now
                $this->wsSendClientClose($clientID, self::WS_STATUS_NORMAL_CLOSE);
            }
            $this->wsRemoveClient($clientID);
        }
        elseif ($opcode == self::WS_OPCODE_TEXT || $opcode == self::WS_OPCODE_BINARY) {
            if ( array_key_exists('message', $this->wsOnEvents) )
                foreach ( $this->wsOnEvents['message'] as $func )
                    $func($clientID, $data, $dataLength, $opcode == self::WS_OPCODE_BINARY);
        }
        else {
            // unknown opcode
            return false;
        }
        return true;
    }
    function wsProcessClientHandshake($clientID, &$buffer) {
        // fetch headers and request line
        $sep = strpos($buffer, "\r\n\r\n");
        if (!$sep) return false;
        $headers = explode("\r\n", substr($buffer, 0, $sep));
        $headersCount = sizeof($headers); // includes request line
        if ($headersCount < 1) return false;
        // fetch request and check it has at least 3 parts (space tokens)
        $request = &$headers[0];
        $requestParts = explode(' ', $request);
        $requestPartsSize = sizeof($requestParts);
        if ($requestPartsSize < 3) return false;
        // check request method is GET
        if (strtoupper($requestParts[0]) != 'GET') return false;
        // check request HTTP version is at least 1.1
        $httpPart = &$requestParts[$requestPartsSize - 1];
        $httpParts = explode('/', $httpPart);
        if (!isset($httpParts[1]) || (float) $httpParts[1] < 1.1) return false;
        // store headers into a keyed array: array[headerKey] = headerValue
        $headersKeyed = array();
        for ($i=1; $i<$headersCount; $i++) {
            $parts = explode(':', $headers[$i]);
            if (!isset($parts[1])) return false;
            $headersKeyed[trim($parts[0])] = trim($parts[1]);
        }
        // check Host header was received
        if (!isset($headersKeyed['Host'])) return false;
        // check Sec-WebSocket-Key header was received and decoded value length is 16
        if (!isset($headersKeyed['Sec-WebSocket-Key'])) return false;
        $key = $headersKeyed['Sec-WebSocket-Key'];
        if (strlen(base64_decode($key)) != 16) return false;
        // check Sec-WebSocket-Version header was received and value is 7
        if (!isset($headersKeyed['Sec-WebSocket-Version']) || (int) $headersKeyed['Sec-WebSocket-Version'] < 7) return false; // should really be != 7, but Firefox 7 beta users send 8
        // work out hash to use in Sec-WebSocket-Accept reply header
        $hash = base64_encode(sha1($key.'258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));
        // build headers
        $headers = array(
            'HTTP/1.1 101 Switching Protocols',
            'Upgrade: websocket',
            'Connection: Upgrade',
            'Sec-WebSocket-Accept: '.$hash
        );
        $headers = implode("\r\n", $headers)."\r\n\r\n";
        // send headers back to client
        $socket = $this->clients[$clientID][0];
        $left = strlen($headers);
        do {
            $sent = @socket_send($socket, $headers, $left, 0);
            if ($sent === false) return false;
            $left -= $sent;
            if ($sent > 0) $headers = substr($headers, $sent);
        }
        while ($left > 0);
        return true;
    }
    // client write functions
    function wsSendClientMessage($clientID, $opcode, $message) {
        // check if client ready state is already closing or closed
        if ($this->clients[$clientID][2] == self::WS_READY_STATE_CLOSING || $this->clients[$clientID][2] == self::WS_READY_STATE_CLOSED) return true;
        // fetch message length
        $messageLength = strlen($message);
        // set max payload length per frame
        $bufferSize = 4096;
        // work out amount of frames to send, based on $bufferSize
        $frameCount = ceil($messageLength / $bufferSize);
        if ($frameCount == 0) $frameCount = 1;
        // set last frame variables
        $maxFrame = $frameCount - 1;
        $lastFrameBufferLength = ($messageLength % $bufferSize) != 0 ? ($messageLength % $bufferSize) : ($messageLength != 0 ? $bufferSize : 0);
        // loop around all frames to send
        for ($i=0; $i<$frameCount; $i++) {
            // fetch fin, opcode and buffer length for frame
            $fin = $i != $maxFrame ? 0 : self::WS_FIN;
            $opcode = $i != 0 ? self::WS_OPCODE_CONTINUATION : $opcode;
            $bufferLength = $i != $maxFrame ? $bufferSize : $lastFrameBufferLength;
            // set payload length variables for frame
            if ($bufferLength <= 125) {
                $payloadLength = $bufferLength;
                $payloadLengthExtended = '';
                $payloadLengthExtendedLength = 0;
            }
            elseif ($bufferLength <= 65535) {
                $payloadLength = self::WS_PAYLOAD_LENGTH_16;
                $payloadLengthExtended = pack('n', $bufferLength);
                $payloadLengthExtendedLength = 2;
            }
            else {
                $payloadLength = self::WS_PAYLOAD_LENGTH_63;
                $payloadLengthExtended = pack('xxxxN', $bufferLength); // pack 32 bit int, should really be 64 bit int
                $payloadLengthExtendedLength = 8;
            }
            // set frame bytes
            $buffer = pack('n', (($fin | $opcode) << 8) | $payloadLength) . $payloadLengthExtended . substr($message, $i*$bufferSize, $bufferLength);
            // send frame
            $socket = $this->clients[$clientID][0];
            $left = 2 + $payloadLengthExtendedLength + $bufferLength;
            do {
                $sent = @socket_send($socket, $buffer, $left, 0);
                if ($sent === false) return false;
                $left -= $sent;
                if ($sent > 0) $buffer = substr($buffer, $sent);
            }
            while ($left > 0);
        }
        return true;
    }
    function wsSendClientClose($clientID, $status=false) {
        // check if client ready state is already closing or closed
        if ($this->clients[$clientID][2] == self::WS_READY_STATE_CLOSING || $this->clients[$clientID][2] == self::WS_READY_STATE_CLOSED) return true;
        // store close status
        $this->clients[$clientID][5] = $status;
        // send close frame to client
        $status = $status !== false ? pack('n', $status) : '';
        $this->wsSendClientMessage($clientID, self::WS_OPCODE_CLOSE, $status);
        // set client ready state to closing
        $this->clients[$clientID][2] = self::WS_READY_STATE_CLOSING;
    }
    // client non-internal functions
    function wsClose($clientID) {
        return $this->wsSendClientClose($clientID, self::WS_STATUS_NORMAL_CLOSE);
    }
    function wsSend($clientID, $message, $binary=false) {
        return $this->wsSendClientMessage($clientID, $binary ? self::WS_OPCODE_BINARY : self::WS_OPCODE_TEXT, $message);
    }
    function log( $message )
    {
        echo date('Y-m-d H:i:s: ') . $message . "\n";
    }
    function bind( $type, $func )
    {
        if ( !isset($this->wsOnEvents[$type]) )
            $this->wsOnEvents[$type] = array();
        $this->wsOnEvents[$type][] = $func;
    }
    function unbind( $type='' )
    {
        if ( $type ) unset($this->wsOnEvents[$type]);
        else $this->wsOnEvents = array();
    }
}












































// prevent the server from timing out
set_time_limit(0);
// include the web sockets server script (the server is started at the far bottom of this file)
// require 'class.PHPWebSocket.php';

// when a client sends data to the server
function wsOnMessage($clientID, $message, $messageLength, $binary) {
    global $Server;
    $ip = long2ip( $Server->clients[$clientID][6] );
    // check if message length is 0
    if ($messageLength == 0) {
        $Server->wsClose($clientID);
        return;
    }
    //The speaker is the only person in the room. Don't let them feel lonely.
    if ( sizeof($Server->clients) == 1 )
        $Server->wsSend($clientID, "There isn't anyone else in the room, but I'll still listen to you. --Your Trusty Server");
    else
        //Send the message to everyone but the person who said it
        foreach ( $Server->clients as $id => $client )
            if ( $id != $clientID )
                $Server->wsSend($id, "Visitor $clientID ($ip) said \"$message\"");
}
// when a client connects
function wsOnOpen($clientID)
{
    global $Server;
    $ip = long2ip( $Server->clients[$clientID][6] );
    $Server->log( "$ip ($clientID) has connected." );
    //Send a join notice to everyone but the person who joined
    foreach ( $Server->clients as $id => $client )
        if ( $id != $clientID )
            $Server->wsSend($id, "Visitor $clientID ($ip) has joined the room.");
}
// when a client closes or lost connection
function wsOnClose($clientID, $status) {
    global $Server;
    $ip = long2ip( $Server->clients[$clientID][6] );
    $Server->log( "$ip ($clientID) has disconnected." );
    //Send a user left notice to everyone in the room
    foreach ( $Server->clients as $id => $client )
        $Server->wsSend($id, "Visitor $clientID ($ip) has left the room.");
}
// start the server
$Server = new PHPWebSocket();
$Server->bind('message', 'wsOnMessage');
$Server->bind('open', 'wsOnOpen');
$Server->bind('close', 'wsOnClose');
// for other computers to connect, you will probably need to change this to your LAN IP or external IP,
// alternatively use: gethostbyaddr(gethostbyname($_SERVER['SERVER_NAME']))

$Server->run();
