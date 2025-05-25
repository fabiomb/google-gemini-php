# google-gemini-php
Función de conexión para Google Gemini desde PHP

La clase GeminiAPIClient permite conectarse a la API de Google Gemini para realizar consultas a un modelo LLM

Requiere crear una variable de entorno GEMINI_API_KEY con la API Key o simplemente reemplazarla por el valor correspondiente

### Caso de uso

En este caso es el ejemplo de uso para https://www.fabio.com.ar/comunidad/ La Comunidad, un chatbot simple que toma una personalidad definida en 'systemInstruction', puede ser modificada a gusto.

### Modelo

El modelo por defecto es gemini-1.5-flash-8b, se puede configurar cualquier otro obteniendo el nombre de esta lista: https://ai.google.dev/gemini-api/docs/models?hl=es-419

### Consumo

La variable 'maxOutputTokens' está limitada a 1024 tokens, modificar esto según la necesidad personal