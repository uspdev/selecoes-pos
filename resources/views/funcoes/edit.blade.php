@extends('master')

@section('styles')
@parent
<style>
  .card-funcoes {
    border: 1px solid coral;
    border-top: 3px solid coral;
  }
  .card-container {
    display: flex;
    flex-wrap: wrap;
  }
  .card-container .card {
    flex: 1 1 21%; /* Ajuste a largura conforme necessário */
    margin: 10px;
  }
  .hover:hover {
    background-color: gainsboro;
  }
  .hide {
    display: none;
  }
  .hover:hover .hide {
    display: inline;
    color: red;
  }
</style>
@endsection

@section('content')
@parent
  <div class="row">
    <div class="col-md-4">
      <div class="card-container">
        <div class="card card-funcoes">
          <div class="card-header">Docentes do Programa</div>
          <div class="card-body">
            @php
              $programa_anterior = '';
              $i = 0;
            @endphp
            @foreach ($programas_docentes as $programa_docente)
              {{ html()->form('post', '')->attribute('id', 'form_funcoes_programas_docentes_' . $i)->open() }}
                @csrf
                @method('put')
                {{ html()->hidden('id') }}
                {{ html()->hidden('funcao', 'Docentes do Programa') }}
                {{ html()->hidden('programa', $programa_docente->nome) }}
                <div class="card my-2">
                  @if ($programa_docente->nome != $programa_anterior)
                    <div class="card-header py-1" style="font-size: 15px;">
                      <span style="color: #ffa000;">{{ $programa_docente->nome }}</span>
                      @include('programas.partials.btn-adicionar-codpes')
                    </div>
                    @php
                      $programa_anterior = $programa_docente->nome;
                      $i++;
                    @endphp
                  @endif
                  @if ($programa_docente->users->count() > 0)
                    <div class="card-body py-1" style="font-size: 14px;">
                      @foreach ($programa_docente->users as $user)
                        <div class="hover">
                          <span>{{ $user->name }}</span>
                          <span class="hide">
                            @include('programas.partials.btn-remover-codpes', ['codpes' => $user->codpes])
                          </span>
                        </div>
                      @endforeach
                    </div>
                  @endif
                </div>
              {{ html()->form()->close() }}
            @endforeach
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="row">
        <div class="col-md-12">
          <div class="card-container">
            <div class="card card-funcoes">
              <div class="card-header">Secretários(as) do Programa</div>
              <div class="card-body">
                @php
                  $programa_anterior = '';
                  $i = 0;
                @endphp
                @foreach ($programas_secretarios as $programa_secretario)
                  {{ html()->form('post', '')->attribute('id', 'form_funcoes_programas_secretarios_' . $i)->open() }}
                    @csrf
                    @method('put')
                    {{ html()->hidden('id') }}
                    {{ html()->hidden('funcao', 'Secretários(as) do Programa') }}
                    {{ html()->hidden('programa', $programa_secretario->nome) }}
                    <div class="card my-2">
                      @if ($programa_secretario->nome != $programa_anterior)
                        <div class="card-header py-1" style="font-size: 15px;">
                          <span style="color: #ffa000;">{{ $programa_secretario->nome }}</span>
                          @include('programas.partials.btn-adicionar-codpes')
                        </div>
                        @php
                          $programa_anterior = $programa_secretario->nome;
                          $i++;
                        @endphp
                      @endif
                      @if ($programa_secretario->users->count() > 0)
                        <div class="card-body py-1" style="font-size: 14px;">
                          @foreach ($programa_secretario->users as $user)
                            <div class="hover">
                              <span>{{ $user->name }}</span>
                              <span class="hide">
                                @include('programas.partials.btn-remover-codpes', ['codpes' => $user->codpes])
                              </span>
                            </div>
                          @endforeach
                        </div>
                      @endif
                    </div>
                  {{ html()->form()->close() }}
                @endforeach
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-12">
          <div class="card-container">
            <div class="card card-funcoes">
              <div class="card-header">Coordenadores(as) do Programa</div>
              <div class="card-body">
                @php
                  $programa_anterior = '';
                  $i = 0;
                @endphp
                @foreach ($programas_coordenadores as $programa_coordenador)
                  {{ html()->form('post', '')->attribute('id', 'form_funcoes_programas_coordenadores_' . $i)->open() }}
                    @csrf
                    @method('put')
                    {{ html()->hidden('id') }}
                    {{ html()->hidden('funcao', 'Coordenadores(as) do Programa') }}
                    {{ html()->hidden('programa', $programa_coordenador->nome) }}
                    <div class="card my-2">
                      @if ($programa_coordenador->nome != $programa_anterior)
                        <div class="card-header py-1" style="font-size: 15px;">
                          <span style="color: #ffa000;">{{ $programa_coordenador->nome }}</span>
                          @include('programas.partials.btn-adicionar-codpes')
                        </div>
                        @php
                          $programa_anterior = $programa_coordenador->nome;
                          $i++;
                        @endphp
                      @endif
                      @if ($programa_coordenador->users->count() > 0)
                        <div class="card-body py-1" style="font-size: 14px;">
                          @foreach ($programa_coordenador->users as $user)
                            <div class="hover">
                              <span>{{ $user->name }}</span>
                              <span class="hide">
                                @include('programas.partials.btn-remover-codpes', ['codpes' => $user->codpes])
                              </span>
                            </div>
                          @endforeach
                        </div>
                      @endif
                    </div>
                  {{ html()->form()->close() }}
                @endforeach
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="row">
        <div class="col-md-12">
          <div class="card-container">
            <div class="card card-funcoes">
              {{ html()->form('post', '')->attribute('id', 'form_funcoes_posgraduacao_servico')->open() }}
                @csrf
                @method('put')
                {{ html()->hidden('id') }}
                {{ html()->hidden('funcao', 'Serviço de Pós-Graduação') }}
                <div class="card-header">Serviço de Pós-Graduação @include('programas.partials.btn-adicionar-codpes')</div>
                <div class="card-body">
                  <div class="card my-2">
                    <div class="card-body py-1" style="font-size: 14px;">
                      @foreach ($posgraduacao_servico_users as $user)
                        <div class="hover">
                          <span>{{ $user->name }}</span>
                          <span class="hide">
                            @include('programas.partials.btn-remover-codpes', ['codpes' => $user->codpes])
                          </span>
                        </div>
                      @endforeach
                    </div>
                  </div>
                </div>
              {{ html()->form()->close() }}
            </div>
          </div>
        </div>
        <div class="col-md-12">
          <div class="card-container">
            <div class="card card-funcoes">
              {{ html()->form('post', '')->attribute('id', 'form_funcoes_posgraduacao_coordenadores')->open() }}
                @csrf
                @method('put')
                {{ html()->hidden('id') }}
                {{ html()->hidden('funcao', 'Coordenadores(as) da Pós-Graduação') }}
                <div class="card-header">Coordenadores(as) da Pós-Graduação @include('programas.partials.btn-adicionar-codpes')</div>
                <div class="card-body">
                  <div class="card my-2">
                    <div class="card-body py-1" style="font-size: 14px;">
                      @foreach ($posgraduacao_coordenadores_users as $user)
                        <div class="hover">
                          <span>{{ $user->name }}</span>
                          <span class="hide">
                            @include('programas.partials.btn-remover-codpes', ['codpes' => $user->codpes])
                          </span>
                        </div>
                      @endforeach
                    </div>
                  </div>
                </div>
              {{ html()->form()->close() }}
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection
