<div id="supplierBillNewModal" class="modal fade bd-example-modal-lg " data-bs-keyboard="false" 
        data-bs-backdrop="static" tabindex="-1" role="dialog"
        aria-labelledby="myLargeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Registrar factura de proveedor</h5>
                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="col-6 text-start">
                    <div class="m-3">
                        <div id="supplierBillNewFile">
                            <label class="form-label" for="supplierBillNewFileInput">Archivo *</label>
                            <input type="file" class="form-control" id="supplierBillNewFileInput" />
                        </div>
                        <div id="supplierBillNewFileMessage"></div>
                    </div>
                </div>
                <div class="row row-cols-1 row-cols-sm-3 row-cols-md-2 row-cols-lg-3 row-cols-xl-4">
                    <div class="col text-center">
                        <div class="m-3">
                            <div id="supplierBillNewName">
                                <div class="form-outline">
                                    
                                    <input type="text" id="supplierBillNewNameInput" class="form-control" value="" disabled/>
                                    <label class="form-label" for="supplierBillNewNameInput">Denominación</label>
                                </div>
                            </div>
                            <div id="supplierBillNewNameMessage"></div>
                        </div>
                    </div>
                    <div class="col text-center">
                        <div class="m-3">
                            <div id="supplierBillNewCif">
                                <div class="form-outline">
                                    <input type="text" id="supplierBillNewCifInput" class="form-control"disabled/>
                                    <label class="form-label" for="supplierBillNewCifInput">Cif </label>
                                </div>
                            </div>
                            <div id="supplierBillNewCifMessage"></div>
                        </div>
                    </div>
                    <div class="col text-center">
                        <div class="m-3">
                            <div id="supplierBillNewAccountingAccount">
                                <div class="form-outline">
                                    <input type="text" id="supplierBillNewAccountingAccountInput" class="form-control"disabled/>
                                    <label class="form-label" for="supplierBillNewAccountingAccountInput">Cuenta contable</label>
                                </div>
                            </div>
                            <div id="supplierBillNewAccountingAccountMessage"></div>
                        </div>
                    </div>
                    <div class="col text-center">
                        <div class="m-3">
                            <div id="supplierBillNewSpendingAccount">
                                <div class="form-outline">
                                    <input type="text" id="supplierBillNewSpendingAccountInput" class="form-control"disabled/>
                                    <label class="form-label" for="supplierBillNewSpendingAccountInput">Cuenta de gasto</label>
                                </div>
                            </div>
                            <div id="supplierBillNewSpendingAccountMessage"></div>
                        </div>
                    </div>
                    <div class="col text-center">
                        <div class="m-3">
                            <div id="supplierBillNewAmount">
                                <div class="form-outline">
                                    <input type="number" id="supplierBillNewAmountInput" class="form-control" />
                                    <label class="form-label" for="supplierBillNewAmountInput">Importe *</label>
                                </div>
                            </div>
                            <div id="supplierBillNewAmountMessage"></div>
                        </div>
                    </div>
                    <div class="col text-center">
                        <div class="m-3">
                            <div id="supplierBillNewRetentionType">
                                <div class="form-outline">
                                    <input hidden />
                                    <select id="supplierBillNewRetentionTypeSelect" class="form-control active" disabled>
                                        <?php
                                            foreach ($db->getRetentionTypes() as $retentionType)
                                                echo('<option value="'.$retentionType["id"].'" class="text-center">'.$retentionType["percentage"].' %</option>');
                                        ?>
                                    </select>
                                    <label class="form-label" for="supplierBillNewRetentionTypeSelect">Retención</label>
                                </div>
                            </div>
                            <div id="supplierBillNewRetentionTypeMessage"></div>
                        </div>
                    </div>
                    <div class="col text-center">
                        <div class="m-3">
                            <div id="supplierBillNewIvaType">
                                <div class="form-outline">
                                    <input hidden />
                                    <select id="supplierBillNewIvaTypeSelect" class="form-control active" disabled>
                                        <?php
                                            foreach ($db->getIvaTypes() as $ivaType)
                                                echo('<option value="'.$ivaType["id"].'" class="text-center">'.$ivaType["id"]."  -  ".$ivaType["percentage"]." %  -  ".$ivaType["recharge"].' %</option>');
                                        ?>
                                    </select>
                                    <label class="form-label" for="supplierBillNewIvaTypeSelect">Código  -  IVA  -  Recargo</label>
                                </div>
                            </div>
                            <div id="supplierBillNewIvaTypeMessage"></div>
                        </div>
                    </div>
                    <div class="col text-center">
                        <div class="m-3">
                            <div id="supplierBillNewTotalAmount">
                                <div class="form-outline">
                                    <input type="number" id="supplierBillNewTotalAmountInput" class="form-control" disabled />
                                    <label class="form-label" for="supplierBillNewTotalAmountInput">Total</label>
                                </div>
                            </div>
                            <div id="supplierBillNewTotalAmountMessage"></div>
                        </div>
                    </div>
                    <div class="col text-center">
                        <div class="m-3">
                            <div id="supplierBillNewBillNumber">
                                <div class="form-outline">
                                    <input type="text" id="supplierBillNewBillNumberInput" class="form-control"/>
                                    <label class="form-label" for="supplierBillNewBillNumberInput">Número de factura *</label>
                                </div>
                            </div>
                            <div id="supplierBillNewBillNumberMessage"></div>
                        </div>
                    </div>
                    <div class="col text-center">
                        <div class="m-3">
                            <div id="supplierBillNewBillingDate">
                                <div class="form-outline">
                                    <input type="date" id="supplierBillNewBillingDateInput" class="form-control" />
                                    <label class="form-label" for="supplierBillNewBillingDateInput">Fecha de factura *</label>
                                </div>
                            </div>
                            <div id="supplierBillNewBillingDateMessage"></div>
                        </div>
                    </div>
                    <div class="col text-center">
                        <div class="m-3">
                            <div id="supplierBillNewCompany">
                                <div class="form-outline">
                                    <input hidden />
                                    <select id="supplierBillNewCompanySelect" class="form-control active">
                                        <?php
                                            echo ('<option value="" class="text-center" selected="selected" disabled>Selecciona la empresa</option>');
                                            foreach ($db->getCompanies() as $company)
                                                echo('<option value="'.$company["id"].'" class="text-center">'.$company["acronym"].' </option>');
                                        ?>
                                    </select>
                                    <label class="form-label" for="supplierBillNewFontTypeSelect">Empresa</label>
                                </div>
                            </div>
                            <div id="supplierBillNewCompanyMessage"></div>
                        </div>
                    </div>                
                    <!-- <div class="col text-center">
                        <div class="m-3">
                            <div id="supplierBillNewCountry">
                                <div class="form-outline">
                                    <input type="text" id="supplierBillNewCountryInput" class="form-control" />
                                    <label class="form-label" for="supplierBillNewCountryInput">País</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col text-center">
                        <div class="m-3">
                            <div id="supplierBillNewCapital">
                                <div class="form-outline">
                                    <input type="text" id="supplierBillNewCapitalInput" class="form-control" />
                                    <label class="form-label" for="supplierBillNewCapitalInput">Capital social</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col text-center">
                        <div class="m-3">
                            <div id="supplierBillNewIban">
                                <div class="form-outline">
                                    <input type="text" id="supplierBillNewIbanInput" class="form-control" />
                                    <label class="form-label" for="supplierBillNewIbanInput">Iban</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col text-center">
                        <div class="m-3">
                            <div id="supplierBillNewConstitutionDate">
                                <div class="form-outline">
                                    <input type="date" id="supplierBillNewConstitutionDateInput" class="form-control" />
                                    <label class="form-label" for="supplierBillNewConstitutionDateInput">Fecha de constitución</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col text-center">
                        <div class="m-3">
                            <div id="supplierBillNewCorporativeColor">
                                <div class="form-outline">
                                    <input type="color" style="width:100%" class="form-control form-control-color mt-2 mw-100"
                                        id="supplierBillNewCorporativeColorInput" title="Escoge un color corporativo"></input>
                                    <label for="supplierBillNewCorporativeColorInput" class="form-label">Color corporativo</label>
                                </div>
                            </div>
                        </div>
                    </div> -->
                    
                </div>
                <!-- <div class="col text-center">
                    <div class="m-3">
                        <div id="supplierBillNewCommercialRegister">
                            <div class="form-outline">
                                <textarea rows="6" cols="40" id="supplierBillNewCommercialRegisterInput" class="form-control" style="resize: none;"
                                    value=""></textarea>
                                <label class="form-label" for="supplierBillNewCommercialRegisterInput">Registro mercantil *</label>
                            </div>
                            <div id="supplierBillNewCommercialRegisterMessage"></div>
                        </div>
                    </div>
                </div>
                <div class="col text-center">
                    <div class="m-3">
                        <div id="supplierBillNewSocialObject">
                            <div class="form-outline">
                                <textarea rows="6" cols="40" id="supplierBillNewSocialObjectInput" class="form-control" style="resize: none;"
                                    value=""></textarea>
                                <label class="form-label" for="supplierBillNewSocialObjectInput">Objeto social</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col text-center">
                    <div class="m-3">
                        <div id="supplierBillNewObservations">
                            <div class="form-outline">
                                <textarea rows="6" cols="40" id="supplierBillNewObservationsInput" style="resize: none;"
                                    class="md-textarea form-control" value=""></textarea>
                                <label class="form-label" for="supplierBillNewObservationsInput">Observaciones</label>
                            </div>
                        </div>
                    </div>
                </div> -->
            </div>
            <div class="modal-footer ">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button id="supplierBillNewSave" class="btn btn-primary">Registrar</button>
            </div>
        </div>
    </div>
</div>
<!-- <script src="js/supplierBillNew.js"></script> -->