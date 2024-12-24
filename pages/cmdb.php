<?php
  $cmdbPlugin = new cmdbPlugin();
  if ($cmdbPlugin->auth->checkAccess($cmdbPlugin->config->get('Plugins','cmdb')['ACL-READ']) == false) {
    die();
  }
  $content = '
  <section class="section">
    <div class="row">
      <div class="col-lg-12">
        <div class="card">
          <div class="card-body">
            <center>
              <h4>CMDB</h4>
              <p>A CMDB.</p>
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
                  data-filter-control-visible="false"
                  data-show-filter-control-switch="true"
                  data-show-refresh="true"
                  data-show-columns="true"
                  data-show-export="true"
                  data-pagination="true"
                  data-toolbar="#toolbar"
                  data-sort-name="Name"
                  data-sort-order="asc"
                  data-page-size="25"
                  data-buttons="cmdbButtons"
                  data-buttons-order= "btnAddRecord,refresh,columns,export,filterControlSwitch",
                  class="table table-striped" id="cmdbTable">
                  <thead>
                    <tr>
                      <th data-field="state" data-checkbox="true"></th>
                      <th data-field="id" data-visible="false" data-filter-control="input">id</th>
                      <th data-field="CPU" data-sortable="true" data-filter-control="input">CPU</th>
                      <th data-field="Memory" data-sortable="true" data-filter-control="input">Memory</th>
                      <th data-field="ServerName" data-sortable="true" data-filter-control="input">Server Name</th>
                      <th data-field="FQDN" data-sortable="true" data-filter-control="input">FQDN</th>
                      <th data-field="IP" data-sortable="true" data-filter-control="input">IP</th>
                      <th data-field="SubnetMask" data-sortable="true" data-filter-control="input">Subnet Mask</th>
                      <th data-field="DNSServers" data-sortable="true" data-filter-control="input">DNS Servers</th>
                      <th data-field="DNSSuffix" data-sortable="true" data-filter-control="input">DNS Suffix</th>
                      <th data-field="Gateway" data-sortable="true" data-filter-control="input">Gateway</th>
                      <th data-field="Description" data-sortable="true" data-filter-control="input">Description</th>
                      <th data-field="OperatingSystem" data-sortable="true" data-filter-control="input">Operating System</th>
                      <th data-formatter="actionFormatter" data-events="actionEvents">Actions</th>
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


  <!-- Record Modal -->
  <div class="modal fade" id="recordModal" tabindex="-1" role="dialog" aria-labelledby="recordModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="recordModalLabel">CMDB record</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
            <span aria-hidden="true"></span>
          </button>
        </div>
        <div class="modal-body" id="recordModelBody">
          <form id="recordForm">
            <div class="row">
              <div class="col-sm-8">
                <p id="modalDescription"></p>
              </div>
            </div>
            <div class="row">
              <div class="col-sm-12">
                <h5 class="card-title">General Information</h5>
                <div class="form-group" style="display:none;">
                  <label for="recordID">ID</label>
                  <input type="text" class="form-control info-field" id="recordID" aria-describedby="recordIDHelp" name="id" disabled hidden>
                </div>
                <div class="form-group">
                  <label for="recordName">Name</label>
                  <input type="text" class="form-control info-field" id="recordName" aria-describedby="recordNameHelp" name="ServerName">
                </div>
                <div class="form-group">
                  <label for="recordFQDN">DNS Name</label>
                  <input type="text" class="form-control info-field" id="recordFQDN" aria-describedby="recordFQDNHelp" name="FQDN">
                </div>
                <div class="form-group">
                  <label for="recordCPU">CPU(s)</label>
                  <input type="text" class="form-control info-field" id="recordCPU" aria-describedby="recordCPUHelp" name="CPU">
                </div>
                <div class="form-group">
                  <label for="recordMemory">Memory (MB)</label>
                  <input type="text" class="form-control info-field" id="recordMemory" aria-describedby="recordMemoryHelp" name="Memory">
                </div>
                <div class="form-group">
                  <label for="recordOS">OS</label>
                  <input type="text" class="form-control info-field" id="recordOS" aria-describedby="recordOSHelp" name="OperatingSystem">
                </div>
                <div class="form-group">
                  <label for="recordIP">IP Address</label>
                  <input type="text" class="form-control info-field" id="recordIP" aria-describedby="recordIPHelp" name="IP">
                </div>
                <div class="form-group">
                  <label for="recordNetmask">Subnet Mask</label>
                  <input type="text" class="form-control info-field" id="recordNetmask" aria-describedby="recordNetmaskHelp" name="SubnetMask">
                </div>
                <div class="form-group">
                  <label for="recordGateway">Gateway</label>
                  <input type="text" class="form-control info-field" id="recordGateway" aria-describedby="recordGatewayHelp" name="Gateway" required>
                </div>
              </div>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          <button id="modalSubmit" type="button" class="btn btn-success" onclick="saveSomething();">Save</button>
        </div>
      </div>
    </div>
  </div>


  <script>
    // CMDB Row Actions Buttons
    function actionFormatter(value, row, index) {
      return [
        ';
        if ($cmdbPlugin->auth->checkAccess($cmdbPlugin->config->get('Plugins','cmdb')['ACL-WRITE'])) { $content .= '
          `<a class="edit" title="Edit">`,
          `<i class="fa fa-pencil"></i>`,
          `</a>&nbsp;`,
          `<a class="delete" title="Delete">`,
          `<i class="fa fa-trash"></i>`,
          "</a>"
        ';}
        $content .= '
      ].join("")
    }

    // CMDB Row Action Events
    window.actionEvents = {
      "click .edit": function (e, value, row, index) {
        populateRecordModal(row);
        $("#recordModal").addClass("editModal").removeClass("newModal").modal("show");
        // Update Submit Button To Edit Record
        $("#modalSubmit").attr("onclick","editSubmit();");
      },
      "click .delete": function (e, value, row, index) {
        if(confirm("Are you sure you want to delete "+row.id+" from the CMDB? This is irriversible.") == true) {
          queryAPI("DELETE","/api/plugin/cmdb/record/"+row.id).done(function(data) {
            if (data["result"] == "Success") {
              toast(data["result"],"",data["message"],"success");
              $("#cmdbTable").bootstrapTable("refresh");
            } else if (data["result"] == "Error") {
              toast(data["result"],"",data["message"],"danger","30000");
            } else {
              toast("Error","","Failed to remove record: "+row.id,"danger","30000");
            }
          }).fail(function() {
            toast("API Error","","Failed to remove record: "+row.id,"danger","30000");
          });
        }
      }
    }

    // CMDB Table Buttons
    function cmdbButtons() {
      return {
        ';
        // Check if user has Write Permission and display add button
        if ($cmdbPlugin->auth->checkAccess($cmdbPlugin->config->get('Plugins','cmdb')['ACL-WRITE'])) { $content .= '
        btnAddRecord: {
          text: "Add Record",
          icon: "bi bi-plus-lg",
          event: function() {
            // Clear all values from new record modal
            $("#recordModal input").val("").removeClass("changed");
            // Update Submit Button To New Record
            $("#modalSubmit").attr("onclick","newSubmit();");
            // Show new record modal
            $("#recordModal").modal("show");
          },
          attributes: {
            title: "Add a CMDB record",
            style: "background-color:#4bbe40;border-color:#4bbe40;"
          }
        }';}
        $content .= '
      }
    }

    // Function to populate Record Modal on inspect
    function populateRecordModal(row) {
      $("#recordModal input").val("");
      $("#recordID").val(row.id);
      $("#recordName").val(row.ServerName);
      $("#recordFQDN").val(row.FQDN);
      $("#recordIP").val(row.IP);
      $("#recordCPU").val(row.CPU);
      $("#recordMemory").val(row.Memory);
      $("#recordNetmask").val(row.SubnetMask);
      $("#recordGateway").val(row.Gateway);
      $("#recordOS").val(row.OperatingSystem);
    }

    function newSubmit() {
      var formData = $("#recordForm .changed").serializeArray();
      
      // Include unchecked checkboxes in the formData
      $("#recordForm input.changed[type=checkbox]").each(function() {
          formData.push({ name: this.name, value: this.checked ? true : false });
      });

      var configData = {};
      formData.forEach(function(item) { 
          var keys = item.name.split("[").map(function(key) {
              return key.replace("]","");
          });
          var temp = configData;
          keys.forEach(function(key, index) {
              if (index === keys.length - 1) {
                  temp[key] = item.value;
              } else {
                  temp[key] = temp[key] || {};
                  temp = temp[key];
              }
          });
      });
      queryAPI("POST","/api/plugin/cmdb/record",configData).done(function(data) {
        if (data["result"] == "Success") {
            toast(data["result"],"",data["message"],"success");
            $("#cmdbTable").bootstrapTable("refresh");
            $("#recordModal").modal("hide");
        } else if (data["result"] == "Error") {
            toast(data["result"],"",data["message"],"danger");
        } else {
            toast("API Error","","Failed to add new CMDB record","danger","30000");
        }
      });
    }

    function editSubmit() {
      var id = $("#recordID").val();
      var formData = $("#recordForm .changed").serializeArray();
      
      // Include unchecked checkboxes in the formData
      $("#recordForm input.changed[type=checkbox]").each(function() {
          formData.push({ name: this.name, value: this.checked ? true : false });
      });

      var configData = {};
      formData.forEach(function(item) { 
          var keys = item.name.split("[").map(function(key) {
              return key.replace("]","");
          });
          var temp = configData;
          keys.forEach(function(key, index) {
              if (index === keys.length - 1) {
                  temp[key] = item.value;
              } else {
                  temp[key] = temp[key] || {};
                  temp = temp[key];
              }
          });
      });
      queryAPI("PATCH","/api/plugin/cmdb/record/"+id,configData).done(function(data) {
        if (data["result"] == "Success") {
            toast(data["result"],"",data["message"],"success");
            $("#cmdbTable").bootstrapTable("refresh");
            $("#recordModal").modal("hide");
        } else if (data["result"] == "Error") {
            toast(data["result"],"",data["message"],"danger");
        } else {
            toast("API Error","","Failed to edit CMDB record","danger","30000");
        }
      });
    }

    // Detect Changes
    $(".info-field").change(function(elem) {
      toast("Configuration","",$(elem.target.previousElementSibling).text()+" has changed.<br><small>Save configuration to apply changes.</small>","warning");
      $(this).addClass("changed");
    });

    // Initialize Table
    $("#cmdbTable").bootstrapTable();
  </script>
';
return $content;