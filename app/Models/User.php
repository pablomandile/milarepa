<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

/**
 * Class User
 *
 * @method bool hasRole(string|int|\Spatie\Permission\Contracts\Role $role, string|null $guardName = null)
 * @method bool hasAnyRole(...$roles)
 * @method bool hasAllRoles(...$roles)
 */

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use HasProfilePhoto;
    use Notifiable;
    use TwoFactorAuthenticatable;
    use HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'accesibilidad',
        'accesibilidad_desc',
        'direccion',
        'pais_id',
        'provincia_id',
        'municipio_id',
        'barrio_id',
        'programa_estudio_id',
        'telefono',
        'whatsapp',
        'fecha_nacimiento',
        'sexo_id',
        'es_maestro',
        'es_coordinador',
        'perfil_completo',
        'msgxmail',
        'msgxwapp',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
    ];

    public function pais()
    {
        return $this->belongsTo(Pais::class, 'pais_id');
    }

    public function membresia()
    {
        return $this->belongsTo(Membresia::class, 'membresia_id');
    }

    public function membresiaUsuario()
    {
        return $this->hasOne(MembresiaUsuario::class, 'user_id');
    }

    public function getMembresiaIdAttribute($value)
    {
        $profile = $this->getMembresiaUsuarioRelation();
        return $profile?->membresia_id ?? $value;
    }

    public function getMembresiaInscripcionFechaAttribute($value)
    {
        $profile = $this->getMembresiaUsuarioRelation();
        return $profile?->membresia_inscripcion_fecha ?? $value;
    }

    public function getMembresiaOnlineAttribute($value)
    {
        $profile = $this->getMembresiaUsuarioRelation();
        if ($profile !== null) {
            return (bool) $profile->membresia_online;
        }

        return (bool) $value;
    }

    public function getMembresiaOnlineMotivoAttribute($value)
    {
        $profile = $this->getMembresiaUsuarioRelation();
        return $profile?->membresia_online_motivo ?? $value;
    }

    public function getInfoTarjetasKadampaAttribute($value)
    {
        $profile = $this->getMembresiaUsuarioRelation();
        if ($profile !== null) {
            return (bool) $profile->info_tarjetas_kadampa;
        }

        return (bool) $value;
    }

    public function getEnvioInfoTkAttribute($value)
    {
        $profile = $this->getMembresiaUsuarioRelation();
        return $profile?->envioInfoTk ?? $value;
    }

    public function provincia()
    {
        return $this->belongsTo(Provincia::class, 'provincia_id');
    }

    public function municipio()
    {
        return $this->belongsTo(Municipio::class, 'municipio_id');
    }

    public function barrio()
    {
        return $this->belongsTo(Barrio::class, 'barrio_id');
    }

    public function sexo()
    {
        return $this->belongsTo(Sexo::class, 'sexo_id');
    }

    public function programaEstudio()
    {
        return $this->belongsTo(ProgramaEstudio::class, 'programa_estudio_id');
    }

    public function estadoCuentasMembresias()
    {
        return $this->hasMany(EstadoCuentaMembresia::class, 'user_id');
    }

    public function inscripciones()
    {
        return $this->hasMany(Inscripcion::class, 'user_id');
    }

    public function updateMembresiaUsuario(array $attributes): void
    {
        $defaults = [
            'membresia_id' => null,
            'membresia_inscripcion_fecha' => null,
            'membresia_online' => false,
            'membresia_online_motivo' => null,
            'info_tarjetas_kadampa' => false,
            'envioInfoTk' => null,
        ];

        $payload = array_merge($defaults, $attributes);

        $hasData =
            !is_null($payload['membresia_id'])
            || !is_null($payload['membresia_inscripcion_fecha'])
            || (bool) $payload['membresia_online']
            || !is_null($payload['membresia_online_motivo'])
            || (bool) $payload['info_tarjetas_kadampa']
            || !is_null($payload['envioInfoTk']);

        if ($hasData) {
            $this->membresiaUsuario()->updateOrCreate([], [
                'membresia_id' => $payload['membresia_id'],
                'membresia_inscripcion_fecha' => $payload['membresia_inscripcion_fecha'],
                'membresia_online' => (bool) $payload['membresia_online'],
                'membresia_online_motivo' => $payload['membresia_online_motivo'],
                'info_tarjetas_kadampa' => (bool) $payload['info_tarjetas_kadampa'],
                'envioInfoTk' => $payload['envioInfoTk'],
            ]);
        } else {
            $this->membresiaUsuario()->delete();
        }

        $this->unsetRelation('membresiaUsuario');
    }

    private function getMembresiaUsuarioRelation(): ?MembresiaUsuario
    {
        if ($this->relationLoaded('membresiaUsuario')) {
            return $this->getRelation('membresiaUsuario');
        }

        if (!$this->exists) {
            return null;
        }

        $profile = $this->membresiaUsuario()->first();
        $this->setRelation('membresiaUsuario', $profile);

        return $profile;
    }
}
