<!-- MODAL CREAR NUEVO ROL (TELEPORTADO AL BODY) -->
@if(auth()->user()->tienePermiso('Roles', 'crear'))
    <template x-teleport="body">
        <div x-show="(typeof openModal !== 'undefined' && openModal) || (typeof openCreateModal !== 'undefined' && openCreateModal)" 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="fixed inset-0 z-[9999] bg-slate-900/40 backdrop-blur-sm flex items-center justify-center p-4" x-cloak>
            
            <div class="bg-white rounded-3xl shadow-2xl max-w-2xl w-full h-[80vh] max-h-[620px] border border-teal-100 flex flex-col overflow-hidden my-auto">
                <!-- Header Fijo -->
                <div class="flex justify-between items-center border-b border-gray-100 px-6 py-4 bg-white flex-shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-teal-100 text-teal-800 flex items-center justify-center font-bold">
                            <i class="bi bi-plus-circle-fill text-xl text-teal-600"></i>
                        </div>
                        <h3 class="text-lg font-extrabold text-gray-800">Crear Nuevo Cargo / Rol</h3>
                    </div>
                    <button @click="if(typeof openModal !== 'undefined') openModal = false; if(typeof openCreateModal !== 'undefined') openCreateModal = false;" type="button" class="text-gray-400 hover:text-gray-600 text-2xl font-bold transition-colors">&times;</button>
                </div>

                <!-- Formulario Flex -->
                <form action="{{ route('roles.store') }}" method="POST" id="formModalNuevoRol" onsubmit="guardarRolModal(event)" class="flex flex-col flex-1 overflow-hidden">
                    @csrf
                    <div class="p-6 space-y-4 flex-1 flex flex-col overflow-hidden">
                        <!-- Campo Nombre -->
                        <div class="flex-shrink-0">
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Nombre del Rol *</label>
                            <input type="text" id="modal_rol_nombre" name="nombre" required placeholder="Ej. Odontólogo, Recepcionista" class="w-full border border-gray-300 rounded-2xl p-3 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 focus:outline-none transition-all">
                            <span id="error_rol_nombre" class="text-xs text-rose-600 mt-1 hidden block"></span>
                        </div>

                        <!-- Cabecera de Permisos -->
                        <div class="flex items-center justify-between flex-shrink-0">
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider">Asignar Permisos</label>
                            <div class="flex gap-2">
                                <button type="button" @click="document.querySelectorAll('#formModalNuevoRol input[type=checkbox]').forEach(cb => cb.checked = true)" class="text-xs bg-teal-100/80 text-teal-800 hover:bg-teal-200/80 font-bold px-3 py-1.5 rounded-xl transition-all flex items-center gap-1">
                                    <i class="bi bi-check-all"></i> Marcar Todos
                                </button>
                                <button type="button" @click="document.querySelectorAll('#formModalNuevoRol input[type=checkbox]').forEach(cb => cb.checked = false)" class="text-xs bg-gray-100 text-gray-600 hover:bg-gray-200 font-bold px-3 py-1.5 rounded-xl transition-all flex items-center gap-1">
                                    <i class="bi bi-x"></i> Desmarcar Todos
                                </button>
                            </div>
                        </div>

                        <!-- Tabla de Permisos con Botones de Icono -->
                        <div class="border border-gray-200/80 rounded-2xl flex-1 overflow-y-auto min-h-0 shadow-2xs">
                            <table class="w-full text-left text-xs">
                                <thead class="bg-teal-800 text-white uppercase font-bold sticky top-0 z-10">
                                    <tr>
                                        <th class="p-3.5">Módulo del Sidebar</th>
                                        <th class="p-3.5 text-center">Ver</th>
                                        <th class="p-3.5 text-center">Crear</th>
                                        <th class="p-3.5 text-center">Editar</th>
                                        <th class="p-3.5 text-center">Eliminar</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 bg-white">
                                    @php
                                        $modulosList = $modulos ?? \App\Models\Modulo::all();
                                        $iconosModulos = [
                                        'Dashboard'    => 'bi-grid-fill',
                                        'Pacientes'    => 'bi-people',
                                        'Citas'        => 'bi-calendar-event',
                                        'Consultas'    => 'bi-file-earmark-medical',
                                        'Consultorios' => 'bi-building',
                                        'Facturación'  => 'bi-credit-card',
                                        'Recetas'      => 'bi-file-earmark-medical',
                                        'Inventario'   => 'bi-box-seam',
                                        'Personal'     => 'bi-person-badge',
                                        'Roles'        => 'bi-shield-lock',
                                        'Reportes'     => 'bi-graph-up',
                                        'Configuración'=> 'bi-gear',
                                        'Bitácora'     => 'bi-journal-text',
                                         ];
                                    @endphp
                                    
                                    @foreach ($modulosList as $mod)
                                        <tr class="hover:bg-teal-50/30 transition-colors">
                                            <td class="p-3.5 font-bold text-gray-800">
                                                <div class="flex items-center gap-2">
                                                    <span class="w-2 h-2 rounded-full bg-teal-500"></span>
                                                    {{ $mod->nombre }}
                                                </div>
                                            </td>
                                            
                                            <!-- Ver (Ojo) -->
                                            <td class="p-3.5 text-center">
                                                <label class="inline-flex items-center justify-center cursor-pointer">
                                                    <input type="checkbox" name="permisos[{{ $mod->id }}][ver]" value="1" class="sr-only peer permiso-checkbox">
                                                    <div class="w-9 h-9 rounded-2xl flex items-center justify-center text-sm font-bold bg-gray-100 text-gray-300 transition-all duration-200 peer-checked:bg-teal-100 peer-checked:text-teal-800 peer-checked:border peer-checked:border-teal-200/80 peer-checked:shadow-2xs hover:scale-105 active:scale-95" title="Ver / Consultar">
                                                        <i class="bi bi-eye-fill"></i>
                                                    </div>
                                                </label>
                                            </td>

                                            <!-- Crear (Más) -->
                                            <td class="p-3.5 text-center">
                                                <label class="inline-flex items-center justify-center cursor-pointer">
                                                    <input type="checkbox" name="permisos[{{ $mod->id }}][crear]" value="1" class="sr-only peer permiso-checkbox">
                                                    <div class="w-9 h-9 rounded-2xl flex items-center justify-center text-sm font-bold bg-gray-100 text-gray-300 transition-all duration-200 peer-checked:bg-emerald-100 peer-checked:text-emerald-800 peer-checked:border peer-checked:border-emerald-200/80 peer-checked:shadow-2xs hover:scale-105 active:scale-95" title="Crear / Registrar">
                                                        <i class="bi bi-plus-lg"></i>
                                                    </div>
                                                </label>
                                            </td>

                                            <!-- Editar (Lápiz) -->
                                            <td class="p-3.5 text-center">
                                                <label class="inline-flex items-center justify-center cursor-pointer">
                                                    <input type="checkbox" name="permisos[{{ $mod->id }}][editar]" value="1" class="sr-only peer permiso-checkbox">
                                                    <div class="w-9 h-9 rounded-2xl flex items-center justify-center text-sm font-bold bg-gray-100 text-gray-300 transition-all duration-200 peer-checked:bg-amber-100 peer-checked:text-amber-800 peer-checked:border peer-checked:border-amber-200/80 peer-checked:shadow-2xs hover:scale-105 active:scale-95" title="Editar / Modificar">
                                                        <i class="bi bi-pencil-fill"></i>
                                                    </div>
                                                </label>
                                            </td>

                                            <!-- Eliminar (Papelera) -->
                                            <td class="p-3.5 text-center">
                                                <label class="inline-flex items-center justify-center cursor-pointer">
                                                    <input type="checkbox" name="permisos[{{ $mod->id }}][eliminar]" value="1" class="sr-only peer permiso-checkbox">
                                                    <div class="w-9 h-9 rounded-2xl flex items-center justify-center text-sm font-bold bg-gray-100 text-gray-300 transition-all duration-200 peer-checked:bg-rose-100 peer-checked:text-rose-800 peer-checked:border peer-checked:border-rose-200/80 peer-checked:shadow-2xs hover:scale-105 active:scale-95" title="Eliminar / Borrar">
                                                        <i class="bi bi-trash-fill"></i>
                                                    </div>
                                                </label>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Footer Fijo -->
                    <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-100 bg-gray-50/50 flex-shrink-0">
                        <button type="button" @click="if(typeof openModal !== 'undefined') openModal = false; if(typeof openCreateModal !== 'undefined') openCreateModal = false;" class="px-5 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-2xl text-sm font-semibold transition-all">Cancelar</button>
                        <button type="submit" class="px-5 py-2.5 bg-teal-600 hover:bg-teal-700 text-white rounded-2xl text-sm font-bold shadow-lg shadow-teal-600/20 transition-all">Guardar Rol</button>
                    </div>
                </form>
            </div>
        </div>
    </template>
@endif

<script>
    function guardarRolModal(e) {
        const select = document.getElementById('selectRolId') || document.getElementById('select_rol_id') || document.getElementById('select_rol_profesional');
        
        if (!select) {
            return true;
        }

        e.preventDefault();
        const nombreInput = document.getElementById('modal_rol_nombre');
        const errorSpan = document.getElementById('error_rol_nombre');
        if (errorSpan) errorSpan.classList.add('hidden');

        const permisos = {};
        document.querySelectorAll('#formModalNuevoRol input[name^="permisos"]').forEach(input => {
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
                nombre: nombreInput.value,
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
            if (data.success || data.rol) {
                const rolId = data.rol?.id || data.rol_id || data.id;
                const rolNombre = data.rol?.nombre || nombreInput.value;
                
                const option = new Option(rolNombre, rolId, true, true);
                select.add(option);

                nombreInput.value = '';
                document.querySelectorAll('#formModalNuevoRol .permiso-checkbox').forEach(cb => cb.checked = false);

                if (window.Alpine) {
                    const el = document.querySelector('[x-data]');
                    if (el && Alpine.$data(el)) {
                        Alpine.$data(el).openModal = false;
                        Alpine.$data(el).openCreateModal = false;
                    }
                }
                if (typeof Swal !== 'undefined') {
                    Swal.fire('¡Éxito!', 'El rol fue creado y asignado.', 'success');
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