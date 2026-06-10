<?php
function callAI($systemPrompt, $userMessage, $temperature = 0.7) {
    if (empty(AI_API_KEY)) throw new Exception('AI_API_KEY not configured');
    
    $data = [
        'model' => AI_MODEL,
        'messages' => [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userMessage]
        ],
        'temperature' => $temperature,
        'max_tokens' => 2000
    ];
    
    // 自动处理API URL - 如果不包含完整路径，自动添加
    $apiUrl = AI_API_URL;
    if (strpos($apiUrl, '/chat/completions') === false) {
        $apiUrl = rtrim($apiUrl, '/') . '/v1/chat/completions';
    }
    
    $ch = curl_init($apiUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . AI_API_KEY
        ],
        CURLOPT_TIMEOUT => 60,
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) throw new Exception('AI request failed: ' . $error);
    if ($httpCode !== 200) throw new Exception('AI error: HTTP ' . $httpCode . ' - ' . $response);
    
    $result = json_decode($response, true);
    if (!isset($result['choices'][0]['message']['content'])) {
        throw new Exception('Invalid AI response: ' . json_encode($result));
    }
    
    return $result['choices'][0]['message']['content'];
}

function analyzeBazi($year, $month, $day, $hour, $gender) {
    $sys = "你是精通命理学的大师，擅长八字命理分析。用专业、温暖的语言解读。";
    $msg = "请分析八字：\n出生：{$year}年{$month}月{$day}日 {$hour}时\n性别：{$gender}\n\n提供：\n1. 八字命盘\n2. 五行分析\n3. 性格特点\n4. 事业运势\n5. 财运\n6. 感情婚姻\n7. 健康建议\n8. 开运建议";
    return callAI($sys, $msg, 0.8);
}

function analyzeName($name, $gender) {
    $sys = "你是姓名学专家，擅长五格剖象法和数理分析。";
    $msg = "分析姓名：{$name}\n性别：{$gender}\n\n提供：\n1. 五格分析\n2. 三才配置\n3. 数理吉凶\n4. 姓名寓意\n5. 性格影响\n6. 事业运势\n7. 改名建议";
    return callAI($sys, $msg, 0.8);
}

function interpretDream($dream) {
    $sys = "你是精通周公解梦和心理学的专家。";
    $msg = "解析梦境：\n{$dream}\n\n提供：\n1. 核心象征\n2. 心理分析\n3. 可能预示\n4. 情绪反映\n5. 生活建议";
    return callAI($sys, $msg, 0.9);
}

function psychologyChat($message, $history = []) {
    $sys = "你是一位温暖、专业的心理咨询师。你的职责是：
1. 倾听并理解用户的情绪和困扰
2. 提供情感支持和心理疏导
3. 给出建设性的生活建议
4. 保持共情、耐心、温暖的态度
5. 遇到严重心理问题时，建议寻求专业帮助

请用温暖、真诚的语气回应，让用户感受到被理解和支持。";
    
    // 构建对话历史
    $contextMsg = "";
    if (!empty($history)) {
        $contextMsg = "对话历史：\n";
        foreach ($history as $turn) {
            $contextMsg .= "用户: {$turn['user']}\n";
            $contextMsg .= "我: {$turn['assistant']}\n\n";
        }
        $contextMsg .= "---\n\n";
    }
    
    $msg = $contextMsg . "用户: {$message}";
    return callAI($sys, $msg, 0.9);
}

