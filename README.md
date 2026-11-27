# TCP/IP Socket Server Using PHP

[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)
[![PHP](https://img.shields.io/badge/PHP-8.1%2B-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![ReactPHP](https://img.shields.io/badge/ReactPHP-Event--Driven-4F5B93)](https://reactphp.org/)

An asynchronous, event-driven TCP/IP socket server and HTTP gateway built with PHP and ReactPHP. It manages persistent TCP client connections for IoT or networked devices, supports token-authenticated `LOGIN`, `DATA` forwarding, and `PING` health checks, and exposes a lightweight HTTP dashboard and query interface to inspect connected devices and push messages in real time.

> **Note:** Dependencies and ReactPHP APIs have been upgraded for PHP 8.1+ and ReactPHP 1.x. The original legacy version is archived under release [`v0.1.0-legacy`](https://github.com/sinhsac/tcp-socket-server-using-php/releases/tag/v0.1.0-legacy).

## Key Features

- **Non-blocking Dual Server Architecture**: Runs both a raw TCP socket server (`0.0.0.0:21196`) and an HTTP server (`0.0.0.0:6869`) concurrently on a single `React\EventLoop`.
- **Token-Authenticated TCP Protocol**: Validates every incoming comma-separated command against a configurable shared secret (`AUTH_STR`).
- **Device Registration & Presence Tracking**: Tracks active device connections, sanitized device names, remote IP addresses, and login timestamps in memory, automatically pruning disconnected clients.
- **Device-to-Device Messaging & Health Checks**: Supports `LOGIN` registration, `DATA` relay between connected devices, and `PING` liveness checks.
- **Built-in HTTP Monitoring & Command Interface**: Serves a Bootstrap HTML table of active TCP clients with an inline form (and query-parameter endpoint) to send messages directly to any connected device.

## Tech Stack

- **Language**: PHP 8.1+ (CLI) with `ext-json`
- **Networking & Concurrency**: [ReactPHP Socket](https://github.com/reactphp/socket) (`react/socket ^1.16`) & [ReactPHP HTTP](https://github.com/reactphp/http) (`react/http ^1.11`)
- **Dependency Management**: Composer

## Project Structure

```text
.
├── main.php                    # Application entry point initializing the ReactPHP event loop and servers
├── config.php                  # Server ports (HTTP_PORT, SOCKET_PORT), auth token (AUTH_STR), and registry keys
├── composer.json               # Composer dependencies (react/socket, react/http, ext-json)
├── modules/
│   ├── SocketServer.php        # TCP socket listener and CSV command parser (LOGIN, DATA, PING)
│   └── HttpServer.php          # HTTP dashboard and query-parameter message relay to TCP clients
├── handler/
│   └── SocketHandler.php       # Device login, connection liveness validation, and data forwarding logic
├── helper/
│   ├── DataUtils.php           # Incoming payload validation helper
│   └── CollectionUtils.php     # Parsed command array length validation helper
└── libs/
    ├── Page.php                # Auxiliary JSON HTTP response helper class
    ├── DevicePage.php          # Auxiliary JSON device listing and messaging handler
    └── func.php                # Placeholder helper file
```

## Prerequisites

- PHP 8.1+ CLI (with `json` extension enabled)
- [Composer](https://getcomposer.org/) 2.x

## Getting Started

1. **Clone the repository**

   ```bash
   git clone https://github.com/sinhsac/tcp-socket-server-using-php.git
   cd tcp-socket-server-using-php
   ```

2. **Install dependencies**

   ```bash
   composer install
   ```

3. **Configure server settings (optional)**

   Edit `config.php` to customize the ports and authentication token before starting the server:

   ```php
   const AUTH_STR = 'b4ac7203c0kda356'; // Shared token required in TCP commands
   const HTTP_PORT = '6869';            // HTTP server port
   const SOCKET_PORT = '21196';         // TCP socket server port
   ```

4. **Start the server**

   ```bash
   php main.php
   ```

   On startup, the server listens on `0.0.0.0:6869` (HTTP) and `0.0.0.0:21196` (TCP).

## Usage

### TCP Socket Protocol

Connect to the TCP server on port `21196` (for example, using `nc` or `telnet`) and send comma-separated commands formatted as:

```text
<AUTH_STR>,<COMMAND>,<DEVICE_ID>[,<PARAMETERS...>]
```

| Command | Format | Description | Response |
| :--- | :--- | :--- | :--- |
| `LOGIN` | `<AUTH_STR>,LOGIN,<DEVICE_ID>,<DEVICE_NAME>` | Registers the TCP connection under `<DEVICE_ID>` with a sanitized device name. | `Login OK` |
| `DATA` | `<AUTH_STR>,DATA,<SOURCE_ID>,<DEST_ID>,<PAYLOAD...>` | Forwards the comma-joined payload to `<DEST_ID>`. | Payload sent to target, or `die` if target is offline |
| `PING` | `<AUTH_STR>,PING,<SOURCE_ID>,<DEST_ID>` | Checks whether `<DEST_ID>` is currently connected. | `OK` if connected, or `die` if offline |

Example session using `nc`:

```bash
nc 127.0.0.1 21196
b4ac7203c0kda356,LOGIN,dev01,Sensor-A
# -> Login OK
b4ac7203c0kda356,PING,dev01,dev01
# -> OK
b4ac7203c0kda356,DATA,dev01,dev02,ON,100
```

### HTTP Dashboard & Message Relay

- **View connected devices**: Open `http://localhost:6869/` in a browser to see all active TCP clients (`Device ID`, `Device Name`, and remote `TCP/IP` address) and send messages via the inline form.
- **Send a message via HTTP**: Send a `GET` request with `id` and `msg` query parameters:

  ```bash
  curl "http://localhost:6869/?id=dev01&msg=TURN_ON"
  ```

  If `dev01` is connected, `TURN_ON\n` is written immediately to its TCP socket and the HTTP response returns `Send data success to dev01 with msg TURN_ON`.

## License

This project is licensed under the MIT License — see the [LICENSE](LICENSE) file for details.
