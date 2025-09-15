#!/usr/bin/env python3
import socket
import threading
import select

# --- Configuration ---
LISTEN_HOST = '127.0.0.1'
LISTEN_PORT = NULL
FORWARD_HOST = '127.0.0.1'
FORWARD_PORT = NULL
BUFFER_SIZE = 4096

def handle_client(client_socket):
    try:
        request_data = client_socket.recv(BUFFER_SIZE)
        client_socket.sendall(b'HTTP/1.1 200 Connection established\r\n\r\n')
        forward_socket = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
        forward_socket.connect((FORWARD_HOST, FORWARD_PORT))
        while True:
            readable_sockets, _, _ = select.select([client_socket, forward_socket], [], [])
            if client_socket in readable_sockets:
                data = client_socket.recv(BUFFER_SIZE)
                if not data: break
                forward_socket.sendall(data)
            if forward_socket in readable_sockets:
                data = forward_socket.recv(BUFFER_SIZE)
                if not data: break
                client_socket.sendall(data)
    except Exception:
        pass
    finally:
        client_socket.close()
        if 'forward_socket' in locals():
            forward_socket.close()

def main():
    server_socket = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
    server_socket.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
    server_socket.bind((LISTEN_HOST, LISTEN_PORT))
    server_socket.listen(50)
    while True:
        client_socket, _ = server_socket.accept()
        thread = threading.Thread(target=handle_client, args=(client_socket,))
        thread.daemon = True
        thread.start()

if __name__ == '__main__':
    main()
