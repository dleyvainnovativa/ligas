<div class="modal fade" id="import-csv-modal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Importar jugadores desde archivo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="small text-secondary">
                    Encabezados aceptados: <code>nombre</code>, <code>email</code>, <code>telefono</code>, <code>pagado</code>.
                    También se aceptan los equivalentes en inglés.
                </p>

                <!-- <input type="file" id="csv-file-input" accept=".csv,text/csv" class="form-control mb-3"> -->
                <input type="file" id="csv-file-input" accept=".csv,.txt,.xlsx,.xls" class="form-control mb-3">

                <div id="csv-preview" class="d-none">
                    <div class="d-flex gap-3 mb-2 small">
                        <span class="text-success"><strong id="csv-valid">0</strong> válidos</span>
                        <span class="text-danger"><strong id="csv-invalid">0</strong> con errores</span>
                    </div>
                    <div class="table-responsive" style="max-height:340px;">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Línea</th>
                                    <th>Nombre</th>
                                    <th>Email</th>
                                    <th>Teléfono</th>
                                    <th class="text-end">Pagado</th>
                                    <th>Errores</th>
                                </tr>
                            </thead>
                            <tbody id="csv-preview-tbody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button class="btn btn-primary" id="csv-import-btn" disabled>
                    <i class="fa-solid fa-file-arrow-up me-1"></i> Importar válidos
                </button>
            </div>
        </div>
    </div>
</div>