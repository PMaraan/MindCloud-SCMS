<!-- Accounts Table -->
  <table class="">
    <thead>
      <tr>
        <th>ID Number</th>
        <th>Email</th>
        <th>First Name</th>
        <th>M.I.</th>
        <th>Last Name</th>
        <th>Roles</th>
        
      </tr>
    </thead>
    <tbody id="table-body">
        
        <tr>
          <td>20231001</td>
          <td>george@lpunetwork.edu.ph</td>
          <td>George</td>
          <td>T</td>
          <td>Santos</td>
          <td class="role-cell" data-editing="false">
            <span class="role-badge Dean" onclick="editRole(this)">Dean</span>
            <button class="btn btn-sm btn-outline-success add-role-btn d-none" onclick="addRole(this)">+</button>
          </td>
            
        </tr>
      
    </tbody>
  </table>