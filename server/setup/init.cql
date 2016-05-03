CREATE KEYSPACE crawly WITH replication = {'class': 'SimpleStrategy', 'replication_factor': '1'}  AND durable_writes = true;

CREATE TABLE crawly.crawl (
    domainname text PRIMARY KEY,
    chunkid int,
    generatormeta text,
    httpstatus int,
    time int
);
CREATE INDEX crawl_generatormeta_idx ON crawly.crawl (generatormeta);
CREATE INDEX crawl_chunkid_idx ON crawly.crawl (chunkid);

CREATE TABLE crawly.reference (
    id text PRIMARY KEY,
    fromdomain text,
    todomain text
);
CREATE INDEX reference_todomain_idx ON crawly.reference (todomain);
CREATE INDEX reference_fromdomain_idx ON crawly.reference (fromdomain);
