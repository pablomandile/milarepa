<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preview de Emails</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
            max-width: 500px;
            width: 100%;
        }
        h1 {
            color: #667eea;
            margin-bottom: 10px;
            text-align: center;
        }
        p {
            color: #666;
            text-align: center;
            margin-bottom: 30px;
            font-size: 14px;
        }
        .button-group {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        a {
            display: block;
            padding: 15px;
            text-align: center;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            transition: all 0.3s;
            font-size: 16px;
        }
        .btn-primary {
            background-color: #667eea;
            color: white;
        }
        .btn-primary:hover {
            background-color: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        .btn-secondary {
            background-color: #764ba2;
            color: white;
        }
        .btn-secondary:hover {
            background-color: #63388a;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(118, 75, 162, 0.4);
        }
        .info {
            background-color: #f0f4ff;
            padding: 15px;
            border-radius: 6px;
            margin-top: 20px;
            font-size: 12px;
            color: #555;
            border-left: 4px solid #667eea;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📧 Preview de Emails</h1>
        <p>Selecciona qué email deseas visualizar</p>
        
        <div class="button-group">
            <a href="./email-preview/inscripcion" class="btn-primary">
                Ver Email con Datos de Prueba
            </a>
            <a href="./email-preview/inscripcion/1" class="btn-secondary">
                Ver Email con Inscripción Real (ID: 1)
            </a>
        </div>
        
        <div class="info">
            <strong>ℹ️ Nota:</strong> La primera opción muestra el email con datos ficticios. La segunda opción cargará una inscripción real de la base de datos (cambia el número 1 por el ID que desees).
        </div>
    </div>
</body>
</html>
