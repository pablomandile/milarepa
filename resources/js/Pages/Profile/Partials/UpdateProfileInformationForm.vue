<script setup>
import { ref } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import ActionMessage from '@/Components/ActionMessage.vue';
import FormSection from '@/Components/FormSection.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Dialog from 'primevue/dialog';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    user: Object,
});

const form = useForm({
    _method: 'PUT',
    name: props.user.name,
    email: props.user.email,
    photo: null,
});

const verificationLinkSent = ref(null);
const photoPreview = ref(null);
const photoInput = ref(null);
const processedPhotoFile = ref(null);
const editing = ref(false);
const confirmVisible = ref(false);
const maxPhotoSizeBytes = 2 * 1024 * 1024;
const compressionTargetBytes = Math.floor(maxPhotoSizeBytes * 0.95);

const updateProfileInformation = () => {
    if (processedPhotoFile.value) {
        form.photo = processedPhotoFile.value;
    } else if (photoInput.value) {
        form.photo = photoInput.value.files[0];
    }

    form.post(route('user-profile-information.update'), {
        errorBag: 'updateProfileInformation',
        preserveScroll: true,
        onSuccess: () => {
            clearPhotoFileInput();
            editing.value = false;
        },
    });
};

const sendEmailVerification = () => {
    verificationLinkSent.value = true;
};

const selectNewPhoto = () => {
    if (!editing.value) return;
    form.clearErrors('photo');
    photoInput.value.click();
};

const updatePhotoPreview = async () => {
    const photo = photoInput.value.files[0];

    if (!photo) {
        processedPhotoFile.value = null;
        return;
    }

    form.clearErrors('photo');
    let finalPhoto = photo;

    if (photo.size > compressionTargetBytes) {
        try {
            finalPhoto = await compressImageToTarget(photo, compressionTargetBytes);
        } catch {
            form.setError('photo', 'No se pudo procesar la imagen seleccionada.');
            processedPhotoFile.value = null;
            return;
        }
    }

    if (finalPhoto.size > maxPhotoSizeBytes) {
        form.setError('photo', 'Tamaño del archivo excedido. Probá una imagen más liviana.');
        processedPhotoFile.value = null;
        return;
    }

    processedPhotoFile.value = finalPhoto;

    const reader = new FileReader();

    reader.onload = (e) => {
        photoPreview.value = e.target.result;
    };

    reader.readAsDataURL(finalPhoto);
};

const deletePhoto = () => {
    if (!editing.value) return;
    router.delete(route('current-user-photo.destroy'), {
        preserveScroll: true,
        onSuccess: () => {
            photoPreview.value = null;
            processedPhotoFile.value = null;
            clearPhotoFileInput();
        },
    });
};

const clearPhotoFileInput = () => {
    processedPhotoFile.value = null;
    if (photoInput.value?.value) {
        photoInput.value.value = null;
    }
};

const loadImageFromFile = (file) => new Promise((resolve, reject) => {
    const image = new Image();
    image.onload = () => resolve(image);
    image.onerror = () => reject(new Error('No se pudo leer la imagen.'));
    image.src = URL.createObjectURL(file);
});

const canvasToBlob = (canvas, type, quality) => new Promise((resolve, reject) => {
    canvas.toBlob((blob) => {
        if (blob) {
            resolve(blob);
        } else {
            reject(new Error('No se pudo convertir la imagen.'));
        }
    }, type, quality);
});

const compressImageToTarget = async (file, targetBytes) => {
    const image = await loadImageFromFile(file);

    const canvas = document.createElement('canvas');
    const context = canvas.getContext('2d');

    if (!context) {
        throw new Error('Canvas no disponible para compresión.');
    }

    const maxDimension = 1920;
    let width = image.width;
    let height = image.height;

    if (width > maxDimension || height > maxDimension) {
        const ratio = Math.min(maxDimension / width, maxDimension / height);
        width = Math.round(width * ratio);
        height = Math.round(height * ratio);
    }

    canvas.width = width;
    canvas.height = height;
    context.drawImage(image, 0, 0, width, height);

    const mimeType = file.type === 'image/png' ? 'image/png' : 'image/jpeg';
    let quality = mimeType === 'image/png' ? undefined : 0.9;
    let blob = await canvasToBlob(canvas, mimeType, quality);

    if (blob.size <= targetBytes) {
        URL.revokeObjectURL(image.src);
        return new File([blob], file.name, { type: blob.type, lastModified: Date.now() });
    }

    if (mimeType === 'image/jpeg') {
        while (blob.size > targetBytes && quality > 0.4) {
            quality -= 0.1;
            blob = await canvasToBlob(canvas, mimeType, quality);
        }
    }

    URL.revokeObjectURL(image.src);
    return new File([blob], file.name, { type: blob.type, lastModified: Date.now() });
};
const openConfirm = () => {
    // Open confirmation modal instead of direct submit
    if (!editing.value) return;
    confirmVisible.value = true;
};

</script>

<template>
    <FormSection @submitted="openConfirm" :no-aside="true">
        <template #form>
            <!-- Profile Photo -->
            <div v-if="$page.props.jetstream.managesProfilePhotos" class="col-span-6 sm:col-span-4">
                <!-- Profile Photo File Input -->
                <input
                    id="photo"
                    ref="photoInput"
                    type="file"
                    class="hidden"
                    @change="updatePhotoPreview"
                >

                <InputLabel for="photo" value="Photo" />

                <!-- Current Profile Photo -->
                <div v-show="! photoPreview" class="mt-2">
                    <img :src="user.profile_photo_url" :alt="user.name" class="rounded-full h-20 w-20 object-cover">
                </div>

                <!-- New Profile Photo Preview -->
                <div v-show="photoPreview" class="mt-2">
                    <span
                        class="block rounded-full w-20 h-20 bg-cover bg-no-repeat bg-center"
                        :style="'background-image: url(\'' + photoPreview + '\');'"
                    />
                </div>

                <SecondaryButton
                    class="mt-2 me-2"
                    type="button"
                    :disabled="!editing"
                    :class="{ 'opacity-50 cursor-not-allowed': !editing }"
                    @click.prevent="selectNewPhoto"
                >
                    Nueva imagen
                </SecondaryButton>

                <SecondaryButton
                    v-if="user.profile_photo_path"
                    type="button"
                    class="mt-2"
                    :disabled="!editing"
                    :class="{ 'opacity-50 cursor-not-allowed': !editing }"
                    @click.prevent="deletePhoto"
                >
                    Quitar foto
                </SecondaryButton>

                <InputError :message="form.errors.photo" class="mt-2" />
            </div>

            <!-- Name -->
            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="name" value="Nombre" />
                <TextInput
                    id="name"
                    v-model="form.name"
                    type="text"
                    class="mt-1 block w-full"
                    :disabled="!editing"
                    required
                    autocomplete="name"
                />
                <InputError :message="form.errors.name" class="mt-2" />
            </div>

            <!-- Email -->
            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="email" value="Correo electrónico" />
                <TextInput
                    id="email"
                    v-model="form.email"
                    type="email"
                    class="mt-1 block w-full"
                    :disabled="!editing"
                    required
                    autocomplete="username"
                />
                <InputError :message="form.errors.email" class="mt-2" />

                <div v-if="$page.props.jetstream.hasEmailVerification && user.email_verified_at === null">
                    <p class="text-sm mt-2">
                        Your email address is unverified.

                        <Link
                            :href="route('verification.send')"
                            method="post"
                            as="button"
                            class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                            @click.prevent="sendEmailVerification"
                        >
                            Click here to re-send the verification email.
                        </Link>
                    </p>

                    <div v-show="verificationLinkSent" class="mt-2 font-medium text-sm text-green-600">
                        A new verification link has been sent to your email address.
                    </div>
                </div>
            </div>
        </template>

        <template #actions>
            <ActionMessage :on="form.recentlySuccessful" class="me-3">
                Saved.
            </ActionMessage>

            <!-- Botón Editar visible inicialmente; al presionarlo, muestra Guardar -->
            <PrimaryButton v-if="!editing" type="button" @click="editing = true" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                Editar
            </PrimaryButton>

            <!-- Botones Cancelar y Guardar cuando está en modo edición -->
            <template v-else>
                <SecondaryButton type="button" @click="editing = false; form.reset()" :disabled="form.processing" class="me-2">
                    Cancelar
                </SecondaryButton>
                <PrimaryButton type="submit" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    Guardar
                </PrimaryButton>
            </template>
        </template>
    </FormSection>

    <!-- Confirmación de guardado -->
    <Dialog v-model:visible="confirmVisible" modal header="Confirmar cambios" :style="{ width: '28rem' }">
        <p class="mb-4">¿Deseas guardar los cambios realizados en tu perfil?</p>
        <div class="flex justify-end gap-2">
            <SecondaryButton type="button" @click="confirmVisible = false">Cancelar</SecondaryButton>
            <PrimaryButton type="button" @click="() => { confirmVisible = false; updateProfileInformation(); }">Confirmar</PrimaryButton>
        </div>
    </Dialog>
</template>
