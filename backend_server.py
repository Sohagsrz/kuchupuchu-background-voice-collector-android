#!/usr/bin/env python3
import http.server
import json
import os
import re
import sys
import uuid

UPLOAD_DIR = os.path.join(os.path.dirname(os.path.abspath(__file__)), "server_uploads")
os.makedirs(UPLOAD_DIR, exist_ok=True)

class RecordingRequestHandler(http.server.BaseHTTPRequestHandler):

    def _send_json(self, status_code, data):
        response_bytes = json.dumps(data).encode("utf-8")
        self.send_response(status_code)
        self.send_header("Content-Type", "application/json")
        self.send_header("Content-Length", str(len(response_bytes)))
        self.end_headers()
        self.wfile.write(response_bytes)

    def do_GET(self):
        print(f"[Backend] GET {self.path} - Headers: Authorization={self.headers.get('Authorization')}")
        if self.path.rstrip("/") == "/api/v1/health":
            self._send_json(200, {
                "status": "ok",
                "version": "1.0.0"
            })
        else:
            self._send_json(404, {"error": "Not Found"})

    def do_POST(self):
        print(f"[Backend] POST {self.path} - Headers: Authorization={self.headers.get('Authorization')}")
        if self.path.rstrip("/") != "/api/v1/recordings":
            self._send_json(404, {"error": "Not Found"})
            return

        content_type = self.headers.get("Content-Type", "")
        if not content_type.startswith("multipart/form-data"):
            self._send_json(400, {"success": False, "error": "Expected multipart/form-data"})
            return

        # Extract boundary
        boundary_match = re.search(r"boundary=(.*)", content_type)
        if not boundary_match:
            self._send_json(400, {"success": False, "error": "Missing boundary in Content-Type"})
            return
        boundary = boundary_match.group(1).strip().strip('"').encode("utf-8")

        content_length = int(self.headers.get("Content-Length", 0))
        body = self.rfile.read(content_length)

        parts = body.split(b"--" + boundary)
        segment_id = None
        device_name = "Google_Pixel_9a"
        file_part_data = None
        orig_filename = None

        for part in parts:
            if not part or part == b"--\r\n" or part == b"--":
                continue
            headers_and_data = part.split(b"\r\n\r\n", 1)
            if len(headers_and_data) < 2:
                continue
            part_headers_raw, part_data = headers_and_data
            part_headers = part_headers_raw.decode("utf-8", errors="ignore")
            # Strip trailing \r\n from data if present
            if part_data.endswith(b"\r\n"):
                part_data = part_data[:-2]

            name_match = re.search(r'name="([^"]+)"', part_headers)
            filename_match = re.search(r'filename="([^"]+)"', part_headers)

            if name_match:
                name = name_match.group(1)
                if name == "segment_id":
                    segment_id = part_data.decode("utf-8", errors="ignore").strip()
                elif name in ("device_name", "phone_name"):
                    raw_dev = part_data.decode("utf-8", errors="ignore").strip()
                    if raw_dev:
                        device_name = raw_dev
                elif name == "file" and filename_match:
                    orig_filename = filename_match.group(1)
                    file_part_data = part_data

        if file_part_data and orig_filename:
            safe_device_dir = re.sub(r'[^a-zA-Z0-9_\-]', '_', device_name).strip('_') or 'Default_Device'
            target_device_dir = os.path.join(UPLOAD_DIR, safe_device_dir)
            os.makedirs(target_device_dir, exist_ok=True)
            saved_path = os.path.join(target_device_dir, orig_filename)
            with open(saved_path, "wb") as f:
                f.write(file_part_data)
            print(f"[Backend] Stored audio file for device '{device_name}' ({len(file_part_data)} bytes): {saved_path}")

        if not segment_id:
            segment_id = str(uuid.uuid4())

        server_recording_id = f"srv_rec_{uuid.uuid4().hex[:12]}"
        print(f"[Backend] Successfully processed upload for segment: {segment_id} -> {server_recording_id}")

        self._send_json(200, {
            "success": True,
            "segment_id": segment_id,
            "recording_id": server_recording_id
        })

    def log_message(self, format, *args):
        # Override to keep logs clean
        sys.stderr.write(f"[Backend Server] {format % args}\n")

def run(port=8080):
    server = http.server.ThreadingHTTPServer(("0.0.0.0", port), RecordingRequestHandler)
    print(f"[Backend] Mock Audio Recorder Server running on port {port}...")
    server.serve_forever()

if __name__ == "__main__":
    port = int(sys.argv[1]) if len(sys.argv) > 1 else 8080
    run(port)
