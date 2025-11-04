@csrf
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
    <div>
        <label class="block text-gray-700">Tipo</label>
        <select name="type" class="w-full border rounded px-3 py-2">
            <option value="1" {{ (old('type', $user->type ?? '') == 1) ? 'selected' : '' }}>Admin</option>
            <option value="2" {{ (old('type', $user->type ?? '') == 2) ? 'selected' : '' }}>Gestor</option>
            <option value="3" {{ (old('type', $user->type ?? '') == 3) ? 'selected' : '' }}>Visualização</option>
        </select>
    </div>
    <div>
        <label class="block text-gray-700">Nome</label>
        <input type="text" name="name" value="{{ old('name', $user->name ?? '') }}" class="w-full border rounded px-3 py-2" required>
    </div>
</div>
<div class="grid grid-cols-1 sm:grid-cols-1 gap-4 mb-4">
    <div>
        <label class="block text-gray-700">Email</label>
        <input type="email" name="email" value="{{ old('email', $user->email ?? '') }}" class="w-full border rounded px-3 py-2" required>
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <label class="block text-gray-700">Senha</label>
        <input type="password" name="password" class="w-full border rounded px-3 py-2">
    </div>
    <div>
        <label class="block text-gray-700">Confirmar Senha</label>
        <input type="password" name="password_confirmation" class="w-full border rounded px-3 py-2">
    </div>
</div>
