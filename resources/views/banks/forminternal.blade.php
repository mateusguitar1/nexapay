<form id="editClientsData">
    {{ csrf_field() }}
    <div class="card-body">
        <div class="row">
            <div class="col-sm-4 mt-2">
                <div class="md-form">
                    <label for="name">Code</label>
                    <input type="text" name="code" id="code" value="" class='form-control'>
                </div>
            </div>
            <div class="col-sm-4 mt-2">
                <div class="md-form">
                    <label for="name">Name</label>
                    <input type="text" name="name" id="name" value="" class='form-control'>
                </div>
            </div>
            <div class="col-sm-4 mt-2">
                <div class="md-form">
                    <label for="name">Holder</label>
                    <input type="text" name="holder" id="holder" value="" class='form-control'>
                </div>
            </div>
            <div class="col-sm-4 mt-2">
                <div class="md-form">
                    <label for="name">Type Account</label>
                    <input type="text" name="type_account" id="type_account" value="" class='form-control'>
                </div>
            </div>
            <div class="col-sm-4 mt-2">
                <div class="md-form">
                    <label for="name">Agency</label>
                    <input type="text" name="agency" id="agency" value="" class='form-control'>
                </div>
            </div>
            <div class="col-sm-4 mt-2">
                <div class="md-form">
                    <label for="name">Account</label>
                    <input type="text" name="account" id="account" value="" class='form-control'>
                </div>
            </div>
            <div class="col-sm-4 mt-2">
                <div class="md-form">
                    <label for="name">Document</label>
                    <input type="text" name="document" id="document" value="" class='form-control'>
                </div>
            </div>
            <div class="col-sm-4 mt-2">
                <div class="md-form">
                    <label for="name">Status</label>
                    <input type="text" name="status" id="status" value="" class='form-control'>
                </div>
            </div>
            <div class="col-sm-4 mt-2">
                <div class="md-form">
                    <label for="name">Prefix</label>
                    <input type="text" name="prefix" id="prefix" value="" class='form-control'>
                </div>
            </div>

            {{-- <div style="clear:both;"></div>

            <div class="col-sm-4 mt-2">
                <div class="md-form">
                    <label for="name">Tipo Chave PIX</label>
                    <select name="type_key_pix" id="" class="form-control">
                        <option value="CNPJ">CNPJ</option>
                        <option value="CPF">CNPJ</option>
                        <option value="EVP">ALEATÓRIA</option>
                        <option value="EMAIL">EMAIL</option>
                        <option value="PHONE">TELEFONE</option>
                    </select>
                </div>
            </div>

            <div class="col-sm-8 mt-2">
                <div class="md-form">
                    <label for="name">Chave PIX Recebimento</label>
                    <input type="text" name="pix_key_withdraw_fee" id="pix_key_withdraw_fee" value="" class='form-control'>
                </div>
            </div> --}}

        </div>
        <input type="hidden" name="id" id="id" value="" class='form-control'>
    </div>
    <div class="card-footer mt-2">
        <div class="row">
            <div class="col-sm-3">
                <button style='width:100%' type="button" data-dismiss="modal" class="btn btn-secondary right width-100">
                    <i class="fa fa-times pr-2" aria-hidden="true"></i>
                     Close
                </button>
            </div>
            <div class="col-sm-3 offset-sm-6">
                <button style='width:100%' type="submit" class="btn btn-success right width-100">
                    <i class="fa fa-save pr-2" aria-hidden="true"></i>
                     Save
                </button>
            </div>
        </div>
    </div>
</form>
