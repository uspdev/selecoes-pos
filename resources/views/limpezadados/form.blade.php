@extends('master')

@section('styles')
@parent
<style>
  #card-limpezadados {
    border: 1px solid coral;
    border-top: 3px solid coral;
  }
</style>
@endsection

@section('content')
@parent
  <div class="row">
    <div class="col-md-7">
      {{ html()->form('post', '')->attribute('id', 'form_limpezadados')->open() }}
        @csrf
        {{ html()->hidden('id') }}
        <div class="card mb-3 w-100" id="card-limpezadados">
          <div class="card-header">
            Limpeza de Dados
          </div>
          <div class="card-body">
            <div class="list_table_div_form">
              <div class="form-group row">
                <label class="col-form-label col-sm-3" for="data_limite">Limpar dados anteriores a (inclusive)&nbsp;<small class="text-required">(*)</small></label>
                <div class="col-sm-9">
                  <input class="form-control datepicker hasDatePicker" type="text" name="data_limite" id="data_limite" value style="width: 106px;">
                </div>
              </div>
              <div class="text-right">
                <button type="submit" class="btn btn-primary">Executar</button>
              </div>
            </div>
          </div>
        </div>
      {{ html()->form()->close() }}
    </div>
  </div>
@endsection

@section('javascripts_bottom')
@parent
  <script src="js/functions.js"></script>
  <script type="text/javascript">
    $(document).ready(function() {
      $('#data_limite').focus();
    });
  </script>
@endsection
