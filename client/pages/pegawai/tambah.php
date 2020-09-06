<?php
	$targetID = __PAGES__[count(__PAGES__) - 1];
?>
<div class="container-fluid page__heading-container">
	<div class="page__heading d-flex align-items-center">
		<div class="flex">
			<nav aria-label="breadcrumb">
				<ol class="breadcrumb mb-0">
					<li class="breadcrumb-item"><a href="<?php echo __HOSTNAME__; ?>/">Home</a></li>
					<li class="breadcrumb-item"><a href="<?php echo __HOSTNAME__; ?>/pegawai">Pengguna</a></li>
					<li class="breadcrumb-item active" aria-current="page">Edit - <?php echo $targetID; ?></li>
				</ol>
			</nav>
			<h4 class="m-0">Edit Data Pengguna</h4>
		</div>
	</div>
</div>


<div class="container-fluid page__container">
	<div class="row card-group-row">
		<div class="col-lg-12 col-md-12">
			<div class="z-0">
				<ul class="nav nav-tabs nav-tabs-custom" role="tablist">
					<li class="nav-item">
						<a href="#tab-awal-1" class="nav-link active" data-toggle="tab" role="tab" aria-selected="true" aria-controls="tab-informasi" >
							<span class="nav-link__count">
								01
							</span>
							Data Diri
						</a>
					</li>
					<li class="nav-item">
						<a href="#tab-awal-2" class="nav-link" data-toggle="tab" role="tab" aria-selected="false">
							<span class="nav-link__count">
								02
							</span>
							Akses
						</a>
					</li>
				</ul>
			</div>
			<div class="card card-body tab-content">
				<div class="tab-pane active show fade" id="tab-awal-1">
					<form>
						<div class="row">
							<div class="col-sm-3">
								<center>
									<img src="<?php echo __HOSTNAME__; ?>/template/assets/images/avatar/demi.png" class="rounded-circle" width="100" alt="Frontted" />
								</center>
							</div>
							<div class="col-sm-9">
								<div class="form-group">
									<label for="txt_nama_pegawai">Email:</label>
									<input type="text" class="form-control" id="txt_email_pegawai" placeholder="Enter your email address ..">
								</div>
								<div class="form-group">
									<label for="txt_nama_pegawai">Nama:</label>
									<input type="text" class="form-control" id="txt_nama_pegawai" placeholder="Nama Pegawai">
								</div>
								<div class="form-group">
									<label for="txt_jabatan">Jabatan:</label>
									<select class="form-control" id="txt_jabatan"></select>
								</div>
								<button type="submit" class="btn btn-success"><i class="fa fa-save"></i> Submit</button>
								<a href="<?php echo __HOSTNAME__; ?>/pegawai" class="btn btn-danger"><i class="fa fa-ban"></i> Kembali</a>
							</div>
						</div>
					</form>
				</div>
				<div class="tab-pane show fade" id="tab-awal-2">
					<div class="col-lg-12 col-md-12 card-group-row__col">
						<div class="row">
							<div class="col-sm-6">
								<h5>Module</h5>
								<table class="table table-bordered largeDataType" id="module-table">
									<thead>
										<tr>
											<th>Module</th>
											<th>Methods</th>
											<th>Access</th>
										</tr>
									</thead>
									<tbody></tbody>
								</table>
							</div>
							<div class="col-sm-6">
								<h5>Access Manager</h5>
								<table class="table table-bordered largeDataType" id="access-table">
									<thead>
										<tr>
											<th>Class</th>
											<th>Methods</th>
											<th>Access</th>
										</tr>
									</thead>
									<tbody></tbody>
								</table>
							</div>
						</div>
					</div>
				</div>
			</div>
			
		</div>
	</div>
</div>