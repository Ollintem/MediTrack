@foreach($usuarios as $usuario)
    <tr>
        <td>{{ $usuario->nombre_completo }}</td>
        <td>
            <!-- Ocultar botón de eliminar si es el usuario ID 1 -->
            @if($usuario->id !== 1)
                <form action="{{ route('users.destroy', $usuario->id) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-sm">Eliminar</button>
                </form>
            @else
                <span class="badge bg-secondary">Protegido</span>
            @endif
        </td>
    </tr>
@endforeach