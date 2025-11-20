<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Gerar Planilha de Benefícios') }}
        </h2>
    </x-slot>

    <div x-data="excelGenerator()" x-init="init()" class="py-8 max-w-7xl mx-auto px-6">
        <!-- Filtros -->
        <div class="bg-white shadow rounded-lg p-6 mb-6">
            <h3 class="text-lg font-semibold mb-4">Filtros</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Período de Admissão -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Período de Admissão</label>
                    <div class="flex items-center gap-2">
                        <input
                            type="text"
                            x-ref="admissionStart"
                            x-model="admissionStart"
                            class="border-gray-300 rounded-md w-full"
                            placeholder="Data inicial"
                        />
                        <span class="text-gray-500">até</span>
                        <input
                            type="text"
                            x-ref="admissionEnd"
                            x-model="admissionEnd"
                            class="border-gray-300 rounded-md w-full"
                            placeholder="Data final"
                        />
                    </div>
                </div>

                <!-- Período de Trabalho -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Período de Trabalho</label>
                    <div class="flex items-center gap-2">
                        <input
                            type="text"
                            x-ref="workStart"
                            x-model="workStart"
                            class="border-gray-300 rounded-md w-full"
                            placeholder="Data inicial"
                        />
                        <span class="text-gray-500">até</span>
                        <input
                            type="text"
                            x-ref="workEnd"
                            x-model="workEnd"
                            class="border-gray-300 rounded-md w-full"
                            placeholder="Data final"
                        />
                    </div>
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <button
                    @click="fetchEmployees"
                    class="bg-indigo-600 text-white px-5 py-2 rounded-md hover:bg-indigo-700 transition"
                >
                    Buscar Funcionários
                </button>
            </div>
        </div>

        <!-- Lista de Funcionários -->
        <template x-if="employees.length > 0">
            <div class="bg-white shadow rounded-lg p-6 mb-6">
                <h3 class="text-lg font-semibold mb-4">Funcionários</h3>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-left border">
                        <thead class="bg-gray-100 text-gray-700 text-sm">
                            <tr>
                                <th class="px-4 py-2 border">Nome</th>
                                <th class="px-4 py-2 border">CPF</th>
                                <th class="px-4 py-2 border">Valor Passagem</th>
                                <th class="px-4 py-2 border">Total</th>
                                <th class="px-4 py-2 border text-center">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(employee, index) in employees" :key="index">
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="px-4 py-2" x-text="employee.full_name"></td>
                                    <td class="px-4 py-2" x-text="employee.cpf"></td>
                                    <td class="px-4 py-2">
                                        <input
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            x-model.number="employee.valorPassagem"
                                            @input="calculateTotal(index)"
                                            class="w-28 border-gray-300 rounded-md"
                                        />
                                    </td>
                                    <td class="px-4 py-2 font-semibold text-gray-700">
                                        R$ <span x-text="employee.total.toFixed(2)"></span>
                                    </td>
                                    <td class="px-4 py-2 text-center">
                                        <button
                                            @click="removeEmployee(index)"
                                            class="text-red-600 hover:text-red-800 font-medium"
                                        >
                                            Remover
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </template>

        <!-- Botão Final -->
        <div class="flex justify-end" x-show="employees.length > 0">
            <button
                @click="generateExcel"
                class="bg-green-600 text-white px-6 py-3 rounded-md hover:bg-green-700 transition"
            >
                Gerar Excel
            </button>
        </div>
    </div>

    <!-- Script -->
    <script>
        function excelGenerator() {
            return {
                admissionStart: '',
                admissionEnd: '',
                workStart: '',
                workEnd: '',
                employees: [],

                init() {
                    // Inicializa o Flatpickr em todos os campos de data
                    ['admissionStart', 'admissionEnd', 'workStart', 'workEnd'].forEach(ref => {
                        flatpickr(this.$refs[ref], {
                            dateFormat: "d/m/Y",
                            locale: "pt",
                            onChange: (dates, dateStr) => this[ref] = dateStr,
                        });
                    });
                },

                fetchEmployees() {
                    if (!this.admissionStart || !this.admissionEnd) {
                        alert('Selecione o período de admissão.');
                        return;
                    }

                    axios.get("{{ route('employees.filter') }}", {
                        params: {
                            admission_start: this.toISO(this.admissionStart),
                            admission_end: this.toISO(this.admissionEnd),
                        }
                    })
                    .then(response => {
                        this.employees = response.data.map(e => ({
                            ...e,
                            valorPassagem: 0,
                            total: 0
                        }));
                    })
                    .catch(error => {
                        console.error(error);
                        alert('Erro ao buscar funcionários.');
                    });
                },

                calculateTotal(index) {
                    const emp = this.employees[index];
                    const diasUteis = this.getWorkingDays();
                    emp.total = (emp.valorPassagem || 0) * diasUteis;
                },

                getWorkingDays() {
                    if (!this.workStart || !this.workEnd) return 0;

                    const [d1, m1, y1] = this.workStart.split('/');
                    const [d2, m2, y2] = this.workEnd.split('/');
                    const start = new Date(`${y1}-${m1}-${d1}`);
                    const end = new Date(`${y2}-${m2}-${d2}`);
                    let count = 0;

                    for (let d = new Date(start); d <= end; d.setDate(d.getDate() + 1)) {
                        const day = d.getDay();
                        if (day !== 0) count++;
                    }

                    return count;
                },

                toISO(dateStr) {
                    if (!dateStr) return '';
                    const [d, m, y] = dateStr.split('/');
                    return `${y}-${m}-${d}`;
                },

                removeEmployee(index) {
                    this.employees.splice(index, 1);
                },

                generateExcel() {
                    if (!this.admissionStart || !this.admissionEnd) {
                        alert('Selecione o período de admissão.');
                        return;
                    }

                    let url = "{{ route('exports.generateCustomIfoodExcel') }}";

                    const params = new URLSearchParams({
                        admission_start: this.toISO(this.admissionStart),
                        admission_end: this.toISO(this.admissionEnd),
                        workStart: this.workStart,
                        workEnd: this.workEnd,
                        employees: JSON.stringify(this.employees),
                    });

                    window.location = url + "?" + params.toString();

                    window.dispatchEvent(
                        new CustomEvent("notify", {
                            detail: {
                                type: "success",
                                message:
                                    data?.Success || "Excel gerado com sucesso!",
                            },
                        })
                    );
                }
            };
        }
    </script>
</x-app-layout>
