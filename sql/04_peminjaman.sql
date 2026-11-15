CREATE TABLE IF NOT EXISTS peminjaman (
    id SERIAL PRIMARY KEY,
    buku_id INTEGER NOT NULL REFERENCES buku(id),
    anggota_id INTEGER NOT NULL REFERENCES anggota(id),
    tanggal_pinjam DATE NOT NULL DEFAULT CURRENT_DATE,
    tanggal_jatuh_tempo DATE NOT NULL DEFAULT (CURRENT_DATE + 7),
    tanggal_kembali DATE,
    status VARCHAR(20) NOT NULL DEFAULT 'dipinjam'
);

ALTER TABLE peminjaman
    ADD COLUMN IF NOT EXISTS tanggal_jatuh_tempo DATE;

UPDATE peminjaman
SET tanggal_jatuh_tempo = tanggal_pinjam + 7
WHERE tanggal_jatuh_tempo IS NULL;

ALTER TABLE peminjaman
    ALTER COLUMN tanggal_jatuh_tempo SET DEFAULT (CURRENT_DATE + 7),
    ALTER COLUMN tanggal_jatuh_tempo SET NOT NULL;

CREATE INDEX IF NOT EXISTS idx_peminjaman_status_anggota
    ON peminjaman(status, anggota_id);

CREATE INDEX IF NOT EXISTS idx_peminjaman_buku_id
    ON peminjaman(buku_id);
