# Configuración de Gmail SMTP para Laravel

## Pasos para configurar Gmail

### 1. Habilitar 2FA en tu cuenta de Gmail
- Ve a myaccount.google.com
- Selecciona "Seguridad" en el menú izquierdo
- Activa "Verificación en dos pasos"

### 2. Generar una contraseña de aplicación
- Ve a myaccount.google.com/apppasswords
- Selecciona "Correo" como aplicación
- Selecciona "Windows Computer" (o tu dispositivo)
- Google te generará una contraseña de 16 caracteres sin espacios
- **IMPORTANTE**: Esta contraseña se usa SOLO para las configuraciones, no es tu contraseña de Gmail normal

### 3. Actualizar el archivo .env

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=tu_email@gmail.com
MAIL_PASSWORD=tu_app_password_de_16_caracteres
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=tu_email@gmail.com
MAIL_FROM_NAME="${APP_NAME}"
```

### Explicación de parámetros:
- **MAIL_HOST**: smtp.gmail.com (servidor SMTP de Gmail)
- **MAIL_PORT**: 587 (puerto TLS recomendado)
- **MAIL_USERNAME**: Tu email completo de Gmail
- **MAIL_PASSWORD**: La contraseña de aplicación de 16 caracteres (NO tu contraseña normal)
- **MAIL_ENCRYPTION**: tls (cifrado seguro)
- **MAIL_FROM_ADDRESS**: El email desde el que se enviarán los correos

### Alternativa con Puerto 465:
Si prefieres usar puerto 465 (SSL):
```env
MAIL_PORT=465
MAIL_ENCRYPTION=ssl
```

### 4. Probar la configuración

Después de actualizar .env, puedes probar con este comando en la terminal:

```bash
php artisan tinker
```

Luego dentro de tinker:
```php
Mail::raw('Correo de prueba desde Gmail', function($message) {
    $message->to('email_destino@example.com')->subject('Prueba de Gmail');
});
```

### Notas de seguridad:
⚠️ **Nunca comitas el .env a Git** (ya está en .gitignore)
⚠️ **La contraseña de aplicación es equivalente a tu contraseña** - guárdala bien
⚠️ **Si comprometes la app password**, puedes eliminarla desde myaccount.google.com/apppasswords
