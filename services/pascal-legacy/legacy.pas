program LegacyCSV;

{$mode objfpc}{$H+}

uses
  SysUtils, DateUtils, Process;

function GetEnvDef(const name, def: string): string;
var v: string;
begin
  v := GetEnvironmentVariable(name);
  if v = '' then Exit(def) else Exit(v);
end;

function RandFloat(minV, maxV: Double): Double;
begin
  Result := minV + Random * (maxV - minV);
end;

procedure GenerateAndCopy();
var
  outDir, fn, fullpath, pghost, pgport, pguser, pgpass, pgdb, copyCmd: string;
  f: TextFile;
  ts: string;
begin
  outDir := GetEnvDef('CSV_OUT_DIR', '/data/csv');
  ts := FormatDateTime('yyyymmdd_hhnnss', Now);
  fn := 'telemetry_' + ts + '.csv';
  fullpath := IncludeTrailingPathDelimiter(outDir) + fn;

  // write CSV
  AssignFile(f, fullpath);
  Rewrite(f);
  Writeln(f, 'recorded_at,is_active,value,description');
  Writeln(f, FormatDateTime('yyyy-mm-dd hh:nn:ss', Now) + ',' +
             'TRUE' + ',' +
             FormatFloat('0.00', RandFloat(10.0, 100.0)) + ',' +
             'Telemetry data generated at ' + FormatDateTime('hh:nn:ss', Now));
  CloseFile(f);

  // Convert to XLSX
  fpSystem('python3 /opt/legacy/csv_to_xlsx.py ' + fullpath + ' ' + ChangeFileExt(fullpath, '.xlsx'));

  // COPY into Postgres
  pghost := GetEnvDef('PGHOST', 'db');
  pgport := GetEnvDef('PGPORT', '5432');
  pguser := GetEnvDef('PGUSER', 'monouser');
  pgpass := GetEnvDef('PGPASSWORD', 'monopass');
  pgdb   := GetEnvDef('PGDATABASE', 'monolith');

  // Use psql with COPY FROM PROGRAM for simplicity
  // Here we call psql reading from file
  copyCmd := 'psql "host=' + pghost + ' port=' + pgport + ' user=' + pguser + ' dbname=' + pgdb + '" ' +
             '-c "\copy telemetry_legacy(recorded_at, is_active, value, description) FROM ''' + fullpath + ''' WITH (FORMAT csv, HEADER true)"';
  // Mask password via env
  SetEnvironmentVariable('PGPASSWORD', pgpass);
  // Execute
  fpSystem(copyCmd);
end;

var period: Integer;
begin
  Randomize;
  period := StrToIntDef(GetEnvDef('GEN_PERIOD_SEC', '300'), 300);
  while True do
  begin
    try
      GenerateAndCopy();
    except
      on E: Exception do
        WriteLn('Legacy error: ', E.Message);
    end;
    Sleep(period * 1000);
  end;
end.
