<div class="container mt-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4>📊 Planilla de notas</h4>
    <a class="btn btn-sm btn-outline-success"
       href="<?= View::e(BASE_URL) ?>/index.php?r=secciones/exportPlanillaCsv&id=<?= View::e($sec['id']) ?>">
      ⬇ Exportar CSV
    </a>
  </div>

  <div class="card shadow-sm mb-4">
    <div class="card-body">
      <h5 class="mb-1">
        <?= View::e($sec['anio'].' '.$sec['nivel_nombre'].' '.$sec['letra'].' · '.$sec['asignatura_nombre']) ?>
      </h5>
      <div class="text-muted">
        Profesor: <?= View::e($sec['profesor_nombre'] ?? '') ?>
      </div>
    </div>
  </div>

  <?php if (empty($alumnos)): ?>
    <div class="alert alert-warning">No hay alumnos matriculados en esta sección.</div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-bordered table-sm align-middle">
        <thead class="table-light">
          <tr>
            <th>RUT</th>
            <th>Alumno</th>
            <?php foreach ($evals as $e): ?>
              <th class="text-center">
                <?= View::e($e['nombre']) ?><br>
                <small class="text-muted"><?= View::e($e['tipo']) ?></small>
                <?php if (!empty($e['ponderacion'])): ?>
                  <br><span class="badge bg-info"><?= $e['ponderacion'] ?>%</span>
                <?php endif; ?>
              </th>
            <?php endforeach; ?>
            <th class="text-center">Promedio</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($alumnos as $a): ?>
            <tr>
              <td><?= View::e($a['rut']) ?></td>
              <td><?= View::e($a['apellidos'].' '.$a['nombres']) ?></td>
              <?php
                $sumNotas=0; $sumPesos=0; $count=0;
                foreach ($evals as $e):
                  $nota = $mapCal[$e['id']][$a['rut']]['nota'] ?? '';
                  echo '<td class="text-center">'.View::e($nota).'</td>';
                  if ($nota!=='') {
                    $notaF = (float)$nota;
                    if ($sumPond>0 && (float)$e['ponderacion']>0) {
                      $sumNotas += $notaF*(float)$e['ponderacion'];
                      $sumPesos += (float)$e['ponderacion'];
                    } else {
                      $sumNotas += $notaF;
                      $count++;
                    }
                  }
                endforeach;
                if ($sumPond>0)
                  $prom = ($sumPesos>0) ? round($sumNotas/$sumPesos,1) : null;
                else
                  $prom = ($count>0) ? round($sumNotas/$count,1) : null;
              ?>
              <td class="text-center fw-bold">
                <?= $prom !== null ? number_format($prom,1,'.','') : '' ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
