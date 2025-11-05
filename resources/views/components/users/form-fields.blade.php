<div class="space-y-4">
    <div>
        <label class="block text-gray-700">Nome</label>
        <input type="text" name="name" class="w-full border rounded px-3 py-2" :value="editUser?.name ?? ''" required>
    </div>

    <div>
        <label class="block text-gray-700">Email</label>
        <input type="email" name="email" class="w-full border rounded px-3 py-2" :value="editUser?.email ?? ''" required>
    </div>

    <div>
        <label class="block text-gray-700">Tipo</label>
        <select name="type" class="w-full border rounded px-3 py-2" :value="editUser?.type ?? ''">
            <option value="1">Admin</option>
            <option value="2">Gestor</option>
            <option value="3">Visualização</option>
        </select>
    </div>

    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-gray-700">{{ isset($edit) ? 'Nova Senha' : 'Senha' }}</label>
            <input type="password" name="password" class="w-full border rounded px-3 py-2" @required(!isset($edit))>
        </div>
        <div>
            <label class="block text-gray-700">Confirmar Senha</label>
            <input type="password" name="password_confirmation" class="w-full border rounded px-3 py-2" @required(!isset($edit))>
        </div>
    </div>
</div>
