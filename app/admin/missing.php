<?php
http_response_code(404);
admin_view('message', ['title' => 'Not found', 'message' => 'That admin page does not exist.']);
