#!/usr/bin/env node

import { getAllSiteProfiles, getSiteProfile, publicSiteProfile, registryPath } from "../server/site-profiles.mjs";

const command = process.argv[2] || "list-json";
const argument = process.argv[3] || "";

try {
  if (command === "list-ids") {
    process.stdout.write(`${Object.keys(getAllSiteProfiles()).join("\n")}\n`);
  } else if (command === "list-json") {
    const profiles = Object.values(getAllSiteProfiles()).map((profile) => publicSiteProfile(profile, false));
    process.stdout.write(`${JSON.stringify(profiles)}\n`);
  } else if (command === "domain") {
    const profile = getSiteProfile(argument);
    if (!profile) throw new Error(`Không tìm thấy website: ${argument}`);
    process.stdout.write(`${profile.domain}\n`);
  } else if (command === "registry-path") {
    process.stdout.write(`${registryPath()}\n`);
  } else {
    throw new Error("Lệnh hỗ trợ: list-ids, list-json, domain, registry-path.");
  }
} catch (error) {
  process.stderr.write(`${error.message}\n`);
  process.exitCode = 1;
}
