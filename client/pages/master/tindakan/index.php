<div class="container-fluid page__heading-container">
	<div class="page__heading d-flex align-items-center">
		<div class="flex">
			<nav aria-label="breadcrumb">
				<ol class="breadcrumb mb-0">
					<li class="breadcrumb-item"><a href="<?php echo __HOSTNAME__; ?>/">Home</a></li>
					<li class="breadcrumb-item" aria-current="page">Master Poli</li>
					<li class="breadcrumb-item active" aria-current="page">Tindakan</li>
				</ol>
			</nav>
		</div>
		<button class="btn btn-sm btn-info" id="tambah-tindakan">
			<i class="fa fa-plus"></i> Tambah Tindakan
		</button>
	</div>
</div>


<div class="container-fluid page__container">
	<div class="row card-group-row">
		<div class="col-lg-12 col-md-12 card-group-row__col">
			<div class="card card-group-row__card card-body card-body-x-lg flex-row align-items-center">
				<table class="table table-bordered" id="table-tindakan">
					<thead>
						<tr>
							<th style="width: 20px;" rowspan="2">No</th>
							<th rowspan="2">Nama Tindakan</th>
							<th colspan="4" style="text-align: center;">Harga</th>
							<th rowspan="2">Aksi</th>
						</tr>
						<tr>
							<th>Kelas III</th>
							<th>Kelas II</th>
							<th>Kelas I</th>
							<th>VIP</th>
						</tr>
					</thead>
					<tbody>
						
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>