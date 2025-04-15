<?php
require("autoload.php");
require("layout/header.php");
require("layout/navbar.php");

if(!isset($_SESSION['auth'])){
    header(header: 'location: login.php');
}
?>
<body>
    <div class="container-fluid">
        <h1 class="text-center text-primary">IMPORT SERVICE PENJUALAN</h1>
        <div class="row mt-5">
            <div class="col-sm-6">
                <div class="row">
                    <div class="col-sm-6">
                        <div class="card">
                            <div class="card-body">
                                <h2>Export Template Service Import</h2>
                                <a href="proses/export/template_faktur.xlsx" class="btn btn-success btn-block mt-3">Download</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="card">
                            <div class="card-body">
                                <h2>Import Data Service</h2>
                                <form action="proses/import_service_pangkas.php" method="POST" enctype="multipart/form-data">
                                    <div class="input-group">
                                        <input type="file" class="form-control" name="filedata">
                                        <span class="input-group-append">
                                            <button class="btn btn-primary">Upload</button>
                                        </span>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card mt-3">
                    <div class="card-body">
                        <h2>Rule Import Excel Service</h2>
                        <ul>
                            <li>Data yang bisa diimport berasal dari <strong>service DPACK</strong></li>
                            <li>Format service yang diimport <strong>harus sesuai</strong> dengan template yang diberikan</li>
                            <li>Format data yang dapat diproses hanya <strong>.xls atau .xlsx</strong></li>
                        </ul>
                        <hr>
                        <h2>Rule Validasi Data Service</h2>
                        <ul>
                            <li>Pengecekan duplikasi data melalui <strong>nomor rangka</strong></li>
                            <li>No. HP Maksimal <strong>14 Digit</strong></li>
                            <li>Data yang akan masuk hanyalah data faktur <strong>Main Dealer</strong></li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="card">
                    <div class="card-body">
                        <h2>Result Import: </h2>
                        <ul id="result-import">
                            <?php if(!empty($_SESSION['import_errors'])): ?>
                                <?php foreach ($_SESSION['import_errors'] as $error): ?>
                                    <li class="text-danger"><?= $error ?></li>
                                <?php endforeach ?>

                                <?php unset($_SESSION['import_errors']); ?>
                            <?php elseif(!empty($_SESSION['import_summary'])): ?>
                                <?php foreach ($_SESSION['import_summary'] as $key => $summary): ?>
                                    <li class="text-<?= $key == "success" ? "success" : "danger" ?>"><?= $summary ?></li>
                                <?php endforeach ?>

                                <?php unset($_SESSION['import_summary']); ?>
                            <?php else: ?>
                                <li>Belum ada data yang diimport</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php require_once 'layout/footer.php' ?>
</body>
</html>