    <?php
      //root/app/views/Accounts_includes/SearchBar.php
    ?>
    
    <!-- Search + Edit Controls -->
    <div class="container-search-bar"><!--container-search-bar open-->
      <div class="row mb-3 align-items-center"><!--row mb-3 align-items-center open-->


        <!-- Search Input-->
        <div class="col-12 col-md-8"> <!--col-12 col-md-8 open-->
          <div class="input-group"><!--inpput-group open-->
            <input type="text" id="search" class="form-control" placeholder="Search">
            <button class="btn filter-btn" type="button"><!--btn filter-btn open-->
              <i class="bi bi-funnel-fill"></i>
            </button><!--btn filter-btn close-->
          </div><!--inpput-group close-->
        </div><!--col-12 col-md-8 close-->
        

        <!-- Edit Buttons -->
        <div class="col-12 col-md-4 mt-2 mt-md-0"><!--col-12 col-md-4 mt-2 mt-md-0 open-->
          <div id="edit-controls" class="d-flex justify-content-md-end justify-content-start gap-2 flex-wrap"><!--edit-controls open-->
            <button id="edit-btn" class="btn btn-outline-primary d-none d-md-inline-flex"><!--edit-btn open-->
              <i class="bi bi-pencil-square"></i>
            </button><!--edit-btn close-->
            <button id="edit-btn-mobile" class="btn btn-outline-primary d-flex d-md-none w-100"><!--edit-btn-mobile open-->
              <i class="bi bi-pencil-square me-1"></i> Edit Mode
            </button><!--edit-btn-mobile close-->
          </div><!--edit-controls close-->
        </div><!--col-12 col-md-4 mt-2 mt-md-0 close-->
        

      </div><!--row mb-3 align-items-center close-->
    </div><!--container-search-bar close-->