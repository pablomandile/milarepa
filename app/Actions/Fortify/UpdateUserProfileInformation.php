<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\UpdatesUserProfileInformation;
use Throwable;

class UpdateUserProfileInformation implements UpdatesUserProfileInformation
{
    /**
     * Validate and update the given user's profile information.
     *
     * @param  array<string, mixed>  $input
     */
    public function update(User $user, array $input): void
    {
        if (isset($input['photo']) && $input['photo'] instanceof UploadedFile && !$input['photo']->isValid()) {
            $errorCode = $input['photo']->getError();
            $errorDetail = $this->uploadErrorDetail($errorCode);

            Log::warning('Error de carga de foto de perfil (archivo invalido).', [
                'user_id' => $user->id,
                'error_code' => $errorCode,
                'error_detail' => $errorDetail,
                'original_name' => $input['photo']->getClientOriginalName(),
                'size_bytes' => $input['photo']->getSize(),
                'mime_type' => $input['photo']->getClientMimeType(),
                'php_upload_max_filesize' => ini_get('upload_max_filesize'),
                'php_post_max_size' => ini_get('post_max_size'),
                'php_upload_tmp_dir' => ini_get('upload_tmp_dir'),
            ]);

            $exception = ValidationException::withMessages([
                'photo' => "No se pudo subir la imagen (codigo {$errorCode}: {$errorDetail}).",
            ]);
            $exception->errorBag = 'updateProfileInformation';
            throw $exception;
        }

        $validator = Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'photo' => ['nullable', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        if ($validator->fails()) {
            Log::warning('Validacion fallida al actualizar perfil.', [
                'user_id' => $user->id,
                'errors' => $validator->errors()->toArray(),
                'has_photo' => isset($input['photo']),
                'php_upload_max_filesize' => ini_get('upload_max_filesize'),
                'php_post_max_size' => ini_get('post_max_size'),
            ]);

            $exception = ValidationException::withMessages($validator->errors()->toArray());
            $exception->errorBag = 'updateProfileInformation';
            throw $exception;
        }

        if (isset($input['photo'])) {
            try {
                $user->updateProfilePhoto($input['photo']);
            } catch (Throwable $exception) {
                Log::error('Excepcion al guardar foto de perfil.', [
                    'user_id' => $user->id,
                    'message' => $exception->getMessage(),
                    'trace' => $exception->getTraceAsString(),
                    'php_upload_max_filesize' => ini_get('upload_max_filesize'),
                    'php_post_max_size' => ini_get('post_max_size'),
                    'php_upload_tmp_dir' => ini_get('upload_tmp_dir'),
                ]);

                $validationException = ValidationException::withMessages([
                    'photo' => 'No se pudo guardar la imagen de perfil. Revisa storage/logs/laravel.log para mas detalle.',
                ]);
                $validationException->errorBag = 'updateProfileInformation';
                throw $validationException;
            }
        }

        if ($input['email'] !== $user->email &&
            $user instanceof MustVerifyEmail) {
            $this->updateVerifiedUser($user, $input);
        } else {
            $user->forceFill([
                'name' => $input['name'],
                'email' => $input['email'],
            ])->save();
        }
    }

    /**
     * Update the given verified user's profile information.
     *
     * @param  array<string, string>  $input
     */
    protected function updateVerifiedUser(User $user, array $input): void
    {
        $user->forceFill([
            'name' => $input['name'],
            'email' => $input['email'],
            'email_verified_at' => null,
        ])->save();

        $user->sendEmailVerificationNotification();
    }

    private function uploadErrorDetail(int $errorCode): string
    {
        return match ($errorCode) {
            UPLOAD_ERR_INI_SIZE => 'Tamaño del archivo excedido',
            UPLOAD_ERR_FORM_SIZE => 'el archivo excede MAX_FILE_SIZE del formulario',
            UPLOAD_ERR_PARTIAL => 'el archivo solo se subio parcialmente',
            UPLOAD_ERR_NO_FILE => 'no se recibio ningun archivo',
            UPLOAD_ERR_NO_TMP_DIR => 'falta la carpeta temporal de subida (upload_tmp_dir)',
            UPLOAD_ERR_CANT_WRITE => 'no se pudo escribir el archivo en disco',
            UPLOAD_ERR_EXTENSION => 'una extension de PHP detuvo la subida',
            default => 'error de subida desconocido',
        };
    }
}
