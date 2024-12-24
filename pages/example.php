<?php
  $cmdbPlugin = new cmdbPlugin();
  if ($cmdbPlugin->auth->checkAccess("CMDB-READ") == false) {
    die();
  }
  return <<<EOF
  <section class="section">
    <div class="row">
      <div class="col-lg-12">
        <div class="card">
          <div class="card-body">
            <center>
              <h4>Example Page</h4>
              <p>Some description.</p>
            </center>
          </div>
        </div>
      </div>
    </div>
    <br>
    <div class="row">
      <div class="col-lg-12">
        <div class="card">
          <div class="card-body">
            <div class="container">
              <div class="row justify-content-center">

                <table data-url="/api/plugin/cmdb/records"
                  data-data-field="data"
                  data-toggle="table"
                  data-search="true"
                  data-filter-control="true"
                  data-show-refresh="true"
                  data-pagination="true"
                  data-toolbar="#toolbar"
                  data-sort-name="Name"
                  data-sort-order="asc"
                  data-page-size="25"
                  data-buttons="cmdbButtons"
                  class="table table-striped" id="cmdbTable">
                  <thead>
                    <tr>
                      <th data-field="state" data-checkbox="true"></th>
                      <th data-field="id" data-visible="false">id</th>
                      <th data-field="CPU" data-sortable="true">CPU</th>
                      <th data-field="Memory" data-sortable="true">Memory</th>
                      <th data-field="Description" data-sortable="true">Group Description</th>
                    </tr>
                  </thead>
                </table>

              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <br>
  </section>

  <script>
    $("#cmdbTable").bootstrapTable();
  </script>
EOF;