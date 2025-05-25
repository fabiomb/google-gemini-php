<?php

class GeminiAPIClient {
    private $apiKey;
    private $model;
    private $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/';

	// En el construct tenemos definido un modelo default, para más información sobre los modelos disponibles https://ai.google.dev/gemini-api/docs/models?hl=es-419
	// por defecto elegí gemini-1.5-flash-8b que es el más rápido y barato disponible con una consistencia adecuada, más barato todavía se puede usar Gemma que es libre
    
    public function __construct($apiKey, $model = 'gemini-1.5-flash-8b') {
        $this->apiKey = $apiKey;
        $this->model = $model;
    }
    
    /**
     * Genera contenido usando la API de Gemini
     * @param string $userInput - El input del usuario que reemplazará INSERT_INPUT_HERE
     * @param bool $stream - Si usar streaming o no (por defecto false)
     * @return string|array - Respuesta de la API
     */
    public function generateContent($userInput, $stream = false) {
        $url = $this->baseUrl . $this->model . ':generateContent';
        
        // Si es streaming, usar la URL de streaming
        if ($stream) {
            $url = $this->baseUrl . $this->model . ':streamGenerateContent';
        }
        
        $payload = $this->buildPayload($userInput);
        
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url . '?key=' . $this->apiKey,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
            ],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        
        // Si es streaming, configurar callback
        if ($stream) {
            curl_setopt($ch, CURLOPT_WRITEFUNCTION, [$this, 'streamCallback']);
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        if ($error) {
            throw new Exception("cURL Error: " . $error);
        }
        
        if ($httpCode !== 200) {
            throw new Exception("HTTP Error: " . $httpCode . " - " . $response);
        }
        
        if ($stream) {
            return true; // El streaming ya se manejó en el callback
        }
        
        return $this->parseResponse($response);
    }
    
    /**
     * Construye el payload para la API
     * @param string $userInput
     * @return array
     */
    private function buildPayload($userInput) {
		
		// El content se divide entre lo que recibe y la configuración
		// en el rol de usuario enviamos el prompt
		// como systemInstruction enviamos el seteo inicial que queremos que tenga siempre definido, esto nos ayuda a imponer límites y condiciones al LLM
		// el maxOutputTokens nos permite controlar la cantidad de tokens máximos que queremos que responda, para limitar costos y tiempos
		
        return [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        ['text' => $userInput]
                    ]
                ]
            ],
            'generationConfig' => [
                'maxOutputTokens' => 1024,
                'responseMimeType' => 'text/plain'
            ],
            'systemInstruction' => [
                'parts' => [
                    ['text' => 'Toma el rol de un bot de red social llamada "La Comunidad" donde @fabiomb es el creador y principal administrador.
Tu trabajo será responder los mensajes que otros usuarios te realicen mencionándolos para que ellos reciban tu respuesta como notificación.
Cada mensaje que recibas será predecidido por toda la conversación, desde la publicación inicial hasta el usuario que te ha mencionado. 
La respuesta deberá hacerse respondiendo al último usuario que te la pregunte y mencione.
Tu nombre en la red es @kaker y responderás como "El kaker" siendo siempre amable y divertido.
Si el usuario te pregunta por un tema que no conoces, puedes decirle que no tienes información al respecto o que no estás seguro, pero siempre de manera amigable y abierta a ayudar.
Si te insultan o te hacen preguntas inapropiadas, puedes responder de manera neutral y sin entrar en conflictos.
Si el usuario te pregunta por temas de política, religión o cualquier otro tema sensible, puedes responder de manera neutral y sin tomar partido.
Puedes terminar las frases con un "Hack the planet" o similar si lo deseas.
En la respuesta no incluyas la pregunta del usuario, solo la respuesta.']

                ]
            ]
        ];
    }
    
    /**
     * Parsea la respuesta de la API
     * @param string $response
     * @return string
     */
    private function parseResponse($response) {
        $data = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Error parsing JSON response: " . json_last_error_msg());
        }
        
        if (isset($data['error'])) {
            throw new Exception("API Error: " . $data['error']['message']);
        }
        
        if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            return $data['candidates'][0]['content']['parts'][0]['text'];
        }
        
        throw new Exception("Unexpected response format");
    }
    
    /**
     * Callback para manejar streaming
     * @param resource $ch
     * @param string $data
     * @return int
     */
    private function streamCallback($ch, $data) {
        // Procesar cada chunk de datos
        $lines = explode("\n", $data);
        foreach ($lines as $line) {
            if (strpos($line, 'data: ') === 0) {
                $jsonData = substr($line, 6);
                if ($jsonData && $jsonData !== '[DONE]') {
                    $decoded = json_decode($jsonData, true);
                    if (isset($decoded['candidates'][0]['content']['parts'][0]['text'])) {
                        echo $decoded['candidates'][0]['content']['parts'][0]['text'];
                        flush();
                    }
                }
            }
        }
        return strlen($data);
    }
    
    /**
     * Genera contenido con streaming
     * @param string $userInput
     */
    public function generateContentStream($userInput) {
        $this->generateContent($userInput, true);
    }
}


try {
    // Obtener la API key desde variable de entorno o definirla directamente
     $apiKey = getenv('GEMINI_API_KEY') ?: 'TU_API_KEY_AQUI';
    
    // Crear instancia del cliente
    $client = new GeminiAPIClient($apiKey);
    
    // Ejemplo de uso sin streaming
    $userInput = "De @juancito: @kaker qué tipo de modelo eres? ¿Qué puedes hacer?";
    $response = $client->generateContent($userInput);
    echo "Respuesta completa:\n" . $response . "\n\n";
    
    // Ejemplo de uso con streaming
    echo "Respuesta con streaming:\n";
    $client->generateContentStream("@kaker ¿Qué otros métodos existen para medir la Tierra?");
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}