@extends('layouts.admin')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Registrar Nuevo Personal</h2>
            <p class="text-sm text-gray-500">Completa los datos para dar de alta un miembro del personal médico.</p>
        </div>
        <a href="{{ route('personal.index') }}" class="text-sm text-gray-500 hover:text-gray-700 font-medium flex items-center gap-1">
            <i class="bi bi-arrow-left"></i> Volver al directorio
        </a>
    </div>
    
    <div class="flex items-center justify-between mb-1">
    <label for="rol_id" class="block text-sm font-medium text-gray-700">Cargo / Rol Profesional</label>
    <a href="{{ route('roles.create') }}" class="text-xs text-teal-600 hover:text-teal-800 font-semibold flex items-center gap-1">
        <i class="bi bi-gear"></i> Gestionar Roles
    </a>
</div>

    @if ($errors->any())
        <div class="p-4 bg-red-50 border border-red-200 rounded-xl text-red-700 text-sm">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 md:p-8">
        <form action="{{ route('personal.store') }}" method="POST" class="space-y-6">
            @csrf

            <h3 class="text-sm font-bold text-gray-500 uppercase tracking-wider mb-4 pb-2 border-b border-gray-100 flex items-center gap-2">
                <i class="bi bi-person-badge"></i> Datos Personales y de Acceso
            </h3>

            <!-- Nombre y Apellido -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Nombre(s)</label>
                    <input type="text" name="nombre" value="{{ old('nombre') }}" required placeholder="Ej. Andres" class="w-full border border-gray-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-teal-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Apellido(s)</label>
                    <input type="text" name="apellido" value="{{ old('apellido') }}" required placeholder="Ej. Morales" class="w-full border border-gray-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-teal-500 focus:outline-none">
                </div>
            </div>

            <!-- Usuario de Correo con sufijo @meditrack.com -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Usuario de Acceso (Correo)</label>
                <div class="flex rounded-lg shadow-sm">
                    <span class="inline-flex items-center px-3 rounded-l-lg border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm">
                        <i class="bi bi-envelope"></i>
                    </span>
                    <input type="text" name="username" value="{{ old('username') }}" required placeholder="amorales" class="flex-1 min-w-0 w-full border border-gray-300 p-2.5 text-sm focus:ring-2 focus:ring-teal-500 focus:outline-none">
                    <span class="inline-flex items-center px-3 rounded-r-lg border border-l-0 border-gray-300 bg-gray-100 text-gray-600 font-medium text-sm">
                        @meditrack.com
                    </span>
                </div>
                <p class="text-xs text-gray-400 mt-1">El correo se generará automáticamente como <strong>usuario@meditrack.com</strong>.</p>
            </div>

            <!-- Contraseña de 8 caracteres exactos -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Contraseña (8 caracteres)</label>
                    <input type="password" name="password" required maxlength="8" minlength="8" placeholder="••••••••" class="w-full border border-gray-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-teal-500 focus:outline-none">
                    <p class="text-xs text-gray-400 mt-1">Debe contener exactamente 8 caracteres.</p>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Confirmar Contraseña</label>
                    <input type="password" name="password_confirmation" required maxlength="8" minlength="8" placeholder="••••••••" class="w-full border border-gray-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-teal-500 focus:outline-none">
                </div>
            </div>

            <h3 class="text-sm font-bold text-gray-500 uppercase tracking-wider pt-4 mb-4 pb-2 border-b border-gray-100 flex items-center gap-2">
                <i class="bi bi-briefcase"></i> Información Profesional y Turno
            </h3>

            <!-- Rol / Especialidad con Dropdown + Botón Modal para agregar/eliminar -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-sm font-semibold text-gray-700">Cargo / Rol Profesional</label>
                        <button type="button" onclick="openRolModal()" class="text-xs text-teal-600 hover:text-teal-700 font-semibold flex items-center gap-1 focus:outline-none">
                            <i class="bi bi-gear"></i> Gestionar Roles
                        </button>
                    </div>
                    <select name="rol_profesional" id="select_rol_profesional" required class="w-full border border-gray-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-teal-500 focus:outline-none bg-white">
                        <option value="" disabled {{ old('rol_profesional') ? '' : 'selected' }}>Selecciona un rol...</option>
                        
                        <!-- Carga  desde la base de datos -->
                        @if(isset($roles))
                            @foreach($roles as $rol)
                                <option value="{{ $rol->nombre }}" {{ old('rol_profesional') == $rol->nombre ? 'selected' : '' }}>{{ $rol->nombre }}</option>
                            @endforeach
                        @endif
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Turno Asignado</label>
                    <select name="turno" required class="w-full border border-gray-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-teal-500 focus:outline-none bg-white">
                        <option value="Mañana" {{ old('turno') == 'Mañana' ? 'selected' : '' }}>Mañana</option>
                        <option value="Tarde" {{ old('turno') == 'Tarde' ? 'selected' : '' }}>Tarde</option>
                        <option value="Noche" {{ old('turno') == 'Noche' ? 'selected' : '' }}>Noche</option>
                        <option value="Completo" {{ old('turno', 'Completo') == 'Completo' ? 'selected' : '' }}>Completo</option>
                    </select>
                </div>
            </div>

            <!-- Teléfono y RUT -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Teléfono de Contacto</label>
                    <input type="text" name="telefono" value="{{ old('telefono') }}" placeholder="5512345678" class="w-full border border-gray-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-teal-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">RUT / Cédula <span class="text-gray-400 font-normal">(Opcional)</span></label>
                    <input type="text" name="rut" value="{{ old('rut') }}" placeholder="12345678-9" class="w-full border border-gray-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-teal-500 focus:outline-none">
                </div>
            </div>

            <!-- Botones de Acción -->
            <div class="pt-4 border-t border-gray-100 flex items-center justify-end gap-3">
                <a href="{{ route('personal.index') }}" class="px-5 py-2.5 rounded-lg border border-gray-300 text-gray-700 text-sm hover:bg-gray-50 font-medium">
                    Cancelar
                </a>
                <button type="submit" class="bg-teal-600 hover:bg-teal-700 text-white font-semibold px-6 py-2.5 rounded-lg text-sm shadow-sm transition flex items-center gap-2">
                    <i class="bi bi-person-check"></i> Registrar Personal
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal para Crear y Gestionar Roles -->
<div id="rolModal" class="fixed inset-0 z-50 hidden bg-gray-900/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-xl border border-gray-200 max-w-md w-full p-6 space-y-4">
        <div class="flex items-center justify-between border-b border-gray-100 pb-3">
            <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                <i class="bi bi-shield-plus text-teal-600"></i> Gestor de Roles
            </h3>
            <button type="button" onclick="closeRolModal()" class="text-gray-400 hover:text-gray-600">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <!-- Formulario para agregar -->
        <form id="formNuevoRol" onsubmit="guardarRol(event)" class="space-y-3">
            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1">Agregar Nuevo Rol</label>
                <div class="flex gap-2">
                    <input type="text" id="modal_rol_nombre" required placeholder="Ej. Odontólogo, Cardiólogo" class="flex-1 border border-gray-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-teal-500 focus:outline-none">
                    <button type="submit" class="px-4 py-2 text-xs text-white bg-teal-600 hover:bg-teal-700 font-semibold rounded-lg shadow-sm">
                        Guardar
                    </button>
                </div>
                <span id="error_rol_nombre" class="text-xs text-red-600 mt-1 hidden block"></span>
            </div>
        </form>

        <hr class="border-gray-100">

        <!-- Lista de roles agregados con opción de eliminar -->
        <div>
            <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Roles Personalizados Existentes</h4>
            <ul id="lista_roles_modal" class="divide-y divide-gray-100 max-h-40 overflow-y-auto text-sm">
                @if(isset($roles) && count($roles) > 0)
                    @foreach($roles as $rol)
                        <li id="rol-item-{{ $rol->id }}" class="py-2 flex items-center justify-between">
                            <span class="text-gray-700 font-medium">{{ $rol->nombre }}</span>
                            <button type="button" onclick="eliminarRol({{ $rol->id }}, '{{ $rol->nombre }}')" class="text-red-500 hover:text-red-700 p-1 rounded hover:bg-red-50 transition" title="Eliminar rol">
                                <i class="bi bi-trash"></i>
                            </button>
                        </li>
                    @endforeach
                @else
                    <li id="sin_roles_msg" class="py-2 text-xs text-gray-400 text-center">No hay roles personalizados registrados.</li>
                @endif
            </ul>
        </div>

        <div class="pt-2 border-t border-gray-100 flex justify-end">
            <button type="button" onclick="closeRolModal()" class="px-4 py-2 text-xs text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 font-medium">Cerrar</button>
        </div>
    </div>
</div>

<!-- Script Modal & Peticiones AJAX -->
<script>
    function openRolModal() {
        document.getElementById('rolModal').classList.remove('hidden');
        document.getElementById('modal_rol_nombre').value = '';
        document.getElementById('error_rol_nombre').classList.add('hidden');
    }

    function closeRolModal() {
        document.getElementById('rolModal').classList.add('hidden');
    }

    function guardarRol(e) {
        e.preventDefault();
        
        const nombre = document.getElementById('modal_rol_nombre').value;
        const errorSpan = document.getElementById('error_rol_nombre');

        errorSpan.classList.add('hidden');

        fetch("{{ route('roles.store') }}", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "Accept": "application/json",
                "X-CSRF-TOKEN": "{{ csrf_token() }}"
            },
            body: JSON.stringify({
                nombre: nombre
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
                // 1. Añadir al Select desplegable del formulario
                const select = document.getElementById('select_rol_profesional');
                const option = document.createElement('option');
                option.value = data.rol.nombre;
                option.textContent = data.rol.nombre;
                option.selected = true;
                select.appendChild(option);

                // 2. Añadir a la lista interna del Modal
                const lista = document.getElementById('lista_roles_modal');
                const sinRolesMsg = document.getElementById('sin_roles_msg');
                if (sinRolesMsg) sinRolesMsg.remove();

                const li = document.createElement('li');
                li.id = `rol-item-${data.rol.id}`;
                li.className = 'py-2 flex items-center justify-between';
                li.innerHTML = `
                    <span class="text-gray-700 font-medium">${data.rol.nombre}</span>
                    <button type="button" onclick="eliminarRol(${data.rol.id}, '${data.rol.nombre}')" class="text-red-500 hover:text-red-700 p-1 rounded hover:bg-red-50 transition" title="Eliminar rol">
                        <i class="bi bi-trash"></i>
                    </button>
                `;
                lista.appendChild(li);

                document.getElementById('modal_rol_nombre').value = '';
            }
        })
        .catch(err => {
            errorSpan.textContent = err.message;
            errorSpan.classList.remove('hidden');
        });
    }

    function eliminarRol(id, nombre) {
        if (!confirm(`¿Estás seguro de eliminar el rol "${nombre}"?`)) return;

        fetch(`/roles/${id}`, {
            method: "DELETE",
            headers: {
                "Content-Type": "application/json",
                "Accept": "application/json",
                "X-CSRF-TOKEN": "{{ csrf_token() }}"
            }
        })
        .then(async response => {
            const data = await response.json();
            if (!response.ok) throw new Error(data.message || 'No se pudo eliminar el rol.');
            return data;
        })
        .then(data => {
            if (data.success) {
                // 1. Remover del listado del modal
                const item = document.getElementById(`rol-item-${id}`);
                if (item) item.remove();

                // 2. Remover del selector principal
                const select = document.getElementById('select_rol_profesional');
                for (let i = 0; i < select.options.length; i++) {
                    if (select.options[i].value === nombre) {
                        select.remove(i);
                        break;
                    }
                }
            }
        })
        .catch(err => {
            alert(err.message);
        });
    }
</script>
@endsection