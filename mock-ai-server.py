#!/usr/bin/env python3
# 模拟AI API服务器 - 返回固定响应
from http.server import HTTPRequestHandler, HTTPServer
import json

class MockAIHandler(HTTPRequestHandler):
    def do_POST(self):
        # 读取请求
        content_length = int(self.headers.get('Content-Length', 0))
        body = self.rfile.read(content_length).decode('utf-8')
        
        try:
            request = json.loads(body)
            print(f"[Mock AI] 收到请求: {request.get('messages', [])[-1].get('content', '')[:50]}")
            
            # 返回固定响应
            response = {
                "choices": [{
                    "message": {
                        "content": "模拟AI响应：测试成功！这是一个固定返回值12345。"
                    }
                }]
            }
            
            self.send_response(200)
            self.send_header('Content-Type', 'application/json')
            self.end_headers()
            self.wfile.write(json.dumps(response).encode())
            
        except Exception as e:
            print(f"[Mock AI] 错误: {e}")
            self.send_response(500)
            self.end_headers()
    
    def log_message(self, format, *args):
        pass  # 静默日志

if __name__ == '__main__':
    port = 8899
    server = HTTPServer(('127.0.0.1', port), MockAIHandler)
    print(f"🤖 模拟AI API运行在 http://127.0.0.1:{port}")
    print("   返回固定响应：12345")
    server.serve_forever()
