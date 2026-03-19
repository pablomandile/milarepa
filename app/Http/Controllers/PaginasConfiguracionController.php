<?php

namespace App\Http\Controllers;

use App\Models\ConfiguracionSistema;
use Illuminate\Http\Request;

class PaginasConfiguracionController extends Controller
{
    private const CLAVE_LOGO_ENTIDAD_PRINCIPAL_NAV = 'mostrar_logo_entidad_principal_nav';
    private const CLAVE_LOGO_ENTIDAD_PRINCIPAL_FOOTER = 'mostrar_logo_entidad_principal_footer';
    private const CLAVE_ENVIO_SEMANAL_INSCRIPCIONES = 'enviar_mail_semanal_inscripciones_actividad';
    private const CLAVE_ENVIO_SEMANAL_DIA = 'envio_mail_semanal_inscripciones_dia';
    private const CLAVE_ENVIO_SEMANAL_HORA = 'envio_mail_semanal_inscripciones_hora';
    private const CLAVE_ENVIO_SEMANAL_DESTINATARIO = 'envio_mail_semanal_inscripciones_destinatario';

    public function index()
    {
        return inertia('Paginas/Configuracion', [
            'mostrar_logo_entidad_principal_nav' => ConfiguracionSistema::obtenerBoolean(self::CLAVE_LOGO_ENTIDAD_PRINCIPAL_NAV, false),
            'mostrar_logo_entidad_principal_footer' => ConfiguracionSistema::obtenerBoolean(self::CLAVE_LOGO_ENTIDAD_PRINCIPAL_FOOTER, false),
            'enviar_mail_semanal_inscripciones_actividad' => ConfiguracionSistema::obtenerBoolean(self::CLAVE_ENVIO_SEMANAL_INSCRIPCIONES, false),
            'envio_mail_semanal_inscripciones_dia' => ConfiguracionSistema::obtenerTexto(self::CLAVE_ENVIO_SEMANAL_DIA, 'viernes'),
            'envio_mail_semanal_inscripciones_hora' => ConfiguracionSistema::obtenerTexto(self::CLAVE_ENVIO_SEMANAL_HORA, '17:00'),
            'envio_mail_semanal_inscripciones_destinatario' => ConfiguracionSistema::obtenerTexto(self::CLAVE_ENVIO_SEMANAL_DESTINATARIO, ''),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'mostrar_logo_entidad_principal_nav' => ['required', 'boolean'],
            'mostrar_logo_entidad_principal_footer' => ['required', 'boolean'],
            'enviar_mail_semanal_inscripciones_actividad' => ['required', 'boolean'],
            'envio_mail_semanal_inscripciones_dia' => ['required', 'in:lunes,martes,miercoles,jueves,viernes,sabado,domingo'],
            'envio_mail_semanal_inscripciones_hora' => ['required', 'date_format:H:i'],
            'envio_mail_semanal_inscripciones_destinatario' => ['nullable', 'email:rfc,dns'],
        ]);

        ConfiguracionSistema::guardarBoolean(
            self::CLAVE_LOGO_ENTIDAD_PRINCIPAL_NAV,
            (bool) $data['mostrar_logo_entidad_principal_nav']
        );

        ConfiguracionSistema::guardarBoolean(
            self::CLAVE_LOGO_ENTIDAD_PRINCIPAL_FOOTER,
            (bool) $data['mostrar_logo_entidad_principal_footer']
        );

        ConfiguracionSistema::guardarBoolean(
            self::CLAVE_ENVIO_SEMANAL_INSCRIPCIONES,
            (bool) $data['enviar_mail_semanal_inscripciones_actividad']
        );

        ConfiguracionSistema::guardarTexto(
            self::CLAVE_ENVIO_SEMANAL_DIA,
            (string) $data['envio_mail_semanal_inscripciones_dia']
        );

        ConfiguracionSistema::guardarTexto(
            self::CLAVE_ENVIO_SEMANAL_HORA,
            (string) $data['envio_mail_semanal_inscripciones_hora']
        );

        ConfiguracionSistema::guardarTexto(
            self::CLAVE_ENVIO_SEMANAL_DESTINATARIO,
            $data['envio_mail_semanal_inscripciones_destinatario'] ?? null
        );

        return back()->with('success', 'Configuración actualizada con éxito.');
    }
}
