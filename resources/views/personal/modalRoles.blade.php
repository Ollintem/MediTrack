<!-- Modal para Crear Nuevo Rol con Permisos -->
<div x-show="openModal" class="fixed inset-0 z-50 overflow-y-auto bg-gray-900/60 backdrop-blur-sm flex items-center justify-center p-4" x-cloak>
    <div class="bg-white rounded-xl shadow-xl max-w-2xl w-full p-6 space-y-6">
        <div class="flex justify-between items-center border-b pb-3">
            <h3 class="text-lg font-bold text-gray-800">Crear Nuevo Cargo / Rol</h3>
            <button @click="openModal = false" class="text-gray-400 hover:text-gray-600 text-xl font-bold">&times;</button>
        </div>

        <form id="formNuevoRolModal" onsubmit="guardarRolModal(event)" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nombre del Rol *</label>
                <input type="text" id="modal_rol_nombre" name="nombre" required placeholder="Ej. Odontólogo, Recepcionista" class="w-full border border-gray-300 rounded-lg p-2.5 text-sm focus:ring-teal-500 focus:border-teal-500">
                <span id="error_rol_nombre" class="text-xs text-red-600 mt-1 hidden block"></span>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Asignar Permisos por Módulo</label>
                <div class="border border-gray-200 rounded-lg overflow-hidden max-h-60 overflow-y-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-gray-100 text-gray-700 uppercase sticky top-0 bg-gray-100 z-10">
                            <tr>
                                <th class="p-3">Módulo</th>
                                <th class="p-3 text-center">Ver</th>
                                <th class="p-3 text-center">Crear</th>
                                <th class="p-3 text-center">Editar</th>
                                <th class="p-3 text-center">Eliminar</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse ($modulos as $mod)
                                <tr class="hover:bg-gray-50">
                                    <td class="p-3 font-semibold text-gray-800">{{ $mod->nombre }}</td>
                                    <td class="p-3 text-center">
                                        <input type="checkbox" name="permisos[{{ $mod->id }}][ver]" value="1" class="permiso-checkbox w-4 h-4 text-teal-600 rounded">
                                    </td>
                                    <td class="p-3 text-center">
                                        <input type="checkbox" name="permisos[{{ $mod->id }}][crear]" value="1" class="permiso-checkbox w-4 h-4 text-teal-600 rounded">
                                    </td>
                                    <td class="p-3 text-center">
                                        <input type="checkbox" name="permisos[{{ $mod->id }}][editar]" value="1" class="permiso-checkbox w-4 h-4 text-teal-600 rounded">
                                    </td>
                                    <td class="p-3 text-center">
                                        <input type="checkbox" name="permisos[{{ $mod->id }}][eliminar]" value="1" class="permiso-checkbox w-4 h-4 text-teal-600 rounded">
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="p-4 text-center text-gray-500">No hay módulos disponibles en la base de datos.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t">
                <button type="button" @click="openModal = false" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm font-semibold">Cancelar</button>
                <button type="submit" class="px-4 py-2 bg-teal-600 hover:bg-teal-700 text-white rounded-lg text-sm font-semibold shadow-sm">Guardar Rol</button>
            </div>
        </form>
    </div>
</div>

<!-- Procesamiento AJAX para el Modal -->
<script>
    function guardarRolModal(e) {
        e.preventDefault();

        const nombreInput = document.getElementById('modal_rol_nombre');
        const nombre = nombreInput.value;
        const errorSpan = document.getElementById('error_rol_nombre');
        if (errorSpan) errorSpan.classList.add('hidden');

        // Capturar permisos marcados
        const permisos = {};
        document.querySelectorAll('#formNuevoRolModal input[name^="permisos"]').forEach(input => {
            if (input.checked) {
                const matches = input.name.match(/\[(\d+)\]\[(\w+)\]/);
                if (matches) {
                    const modId = matches[1];
                    const accion = matches[2];
                    if (!permisos[modId]) permisos[modId] = {};
                    permisos[modId][accion] = 1;
                }
            }
        });

        fetch("{{ route('roles.store') }}", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "Accept": "application/json",
                "X-CSRF-TOKEN": "{{ csrf_token() }}"
            },
            body: JSON.stringify({
                nombre: nombre,
                permisos: permisos
            })
        })
        .then(async response => {
            const data = await response.json();
            if (!response.ok) {
                let mensaje = data.message || 'Error al guardar el rol.';
                if (data.errors && data.errors.nombre) {
                    mensaje = data.errors.nombre[0];
                }
                throw new Error(mensaje);
            }
            return data;
        })
        .then(data => {
            if (data.success) {
                const select = document.getElementById('select_rol_profesional');

                if (select) {
                    // CASO 1: Desde la vista 'Registrar Personal'
                    // 1. Añadir el rol recién creado al selector y seleccionarlo
                    const option = document.createElement('option');
                    option.value = data.rol.nombre;
                    option.textContent = data.rol.nombre;
                    option.selected = true;
                    select.appendChild(option);

                    // 2. Limpiar formulario del modal
                    nombreInput.value = '';
                    document.querySelectorAll('#formNuevoRolModal .permiso-checkbox').forEach(cb => cb.checked = false);

                    // 3. Cerrar la ventana del modal Alpine en la misma vista
                    if (window.Alpine) {
                        const el = document.querySelector('[x-data]');
                        if (el && Alpine.$data(el)) {
                            Alpine.$data(el).openModal = false;
                        }
                    }
                } else {
                    // CASO 2: Desde la vista 'Roles y Permisos'
                    // Recargar la pantalla para mostrar la nueva fila en la matriz
                    window.location.reload();
                }
            }
        })
        .catch(err => {
            if (errorSpan) {
                errorSpan.textContent = err.message;
                errorSpan.classList.remove('hidden');
            } else {
                alert(err.message);
            }
        });
    }
</script>