<div class="container-fluid page__heading-container">
	<div class="page__heading d-flex align-items-center">
		<div class="flex">
			<nav aria-label="breadcrumb">
				<ol class="breadcrumb mb-0">
					<li class="breadcrumb-item"><a href="<?php echo __HOSTNAME__; ?>/">Home</a></li>
					<li class="breadcrumb-item active" aria-current="page">Terminologi Manager</li>
				</ol>
			</nav>
			<h2 class="m-0">Terminologi</h2>
		</div>
		<a href="<?php echo __HOSTNAME__; ?>/terminologi/tambah" class="btn btn-info ml-3">Tambah Terminologi</a>
	</div>
</div>


<div class="container-fluid page__container">
	<div class="row card-group-row">
		<div class="col-lg-12 col-md-12 card-group-row__col">
			<div class="card card-group-row__card card-body card-body-x-lg flex-row align-items-center">
				<div class="table-responsive border-bottom" data-toggle="lists">
					<table class="table table-striped" id="table-terminologi">
						<thead>
							<tr>
								<th style="width: 20px;">No</th>
								<th>Term</th>
								<th width="30%">Aksi</th>
							</tr>
						</thead>
						<tbody></tbody>
					</table>
				</div>
			</div>
		</div>
	</div>
</div>