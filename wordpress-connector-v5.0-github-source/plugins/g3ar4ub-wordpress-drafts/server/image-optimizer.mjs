import { constants as fsConstants } from "node:fs";
import fs from "node:fs/promises";
import os from "node:os";
import path from "node:path";
import { execFile } from "node:child_process";
import { promisify } from "node:util";

const execFileAsync = promisify(execFile);

export const COVER_WIDTH = 800;
export const COVER_HEIGHT = 400;
export const MAX_WEBP_BYTES = 100 * 1024;

const QUALITY_STEPS = [86, 82, 78, 74, 70, 66, 62, 58, 54, 50, 46, 42, 38, 34, 30, 26, 22, 18, 14, 10];

async function findExecutable(names) {
  const pathDirs = (process.env.PATH || "").split(path.delimiter).filter(Boolean);
  for (const name of names) {
    for (const directory of pathDirs) {
      const candidate = path.join(directory, name);
      try {
        await fs.access(candidate, fsConstants.X_OK);
        return { name, path: candidate };
      } catch {
        // Continue searching PATH.
      }
    }
  }
  return null;
}

async function availableBackends() {
  const backends = [];
  const imageMagickNames = process.platform === "win32" ? ["magick.exe"] : ["magick", "convert"];
  const ffmpegNames = process.platform === "win32" ? ["ffmpeg.exe"] : ["ffmpeg"];
  const imageMagick = await findExecutable(imageMagickNames);
  if (imageMagick) backends.push({ type: "imagemagick", ...imageMagick });

  const ffmpeg = await findExecutable(ffmpegNames);
  if (ffmpeg) backends.push({ type: "ffmpeg", ...ffmpeg });
  return backends;
}

async function encodeWithImageMagick(executable, inputPath, outputPath, quality) {
  const args = [
    inputPath,
    "-auto-orient",
    "-strip",
    "-resize",
    `${COVER_WIDTH}x${COVER_HEIGHT}^`,
    "-gravity",
    "center",
    "-extent",
    `${COVER_WIDTH}x${COVER_HEIGHT}`,
    "-quality",
    String(quality),
    "-define",
    "webp:method=6",
    outputPath,
  ];
  await execFileAsync(executable, args, { timeout: 120000, maxBuffer: 1024 * 1024 });
}

async function encodeWithFfmpeg(executable, inputPath, outputPath, quality) {
  const filter = [
    `scale=${COVER_WIDTH}:${COVER_HEIGHT}:force_original_aspect_ratio=increase`,
    `crop=${COVER_WIDTH}:${COVER_HEIGHT}`,
  ].join(",");
  const args = [
    "-hide_banner",
    "-loglevel",
    "error",
    "-y",
    "-i",
    inputPath,
    "-vf",
    filter,
    "-frames:v",
    "1",
    "-c:v",
    "libwebp",
    "-compression_level",
    "6",
    "-quality",
    String(quality),
    outputPath,
  ];
  await execFileAsync(executable, args, { timeout: 120000, maxBuffer: 2 * 1024 * 1024 });
}

function webpName(inputPath) {
  const base = path.basename(inputPath, path.extname(inputPath));
  const safe = base.replace(/[^a-zA-Z0-9._-]+/g, "-").replace(/^-+|-+$/g, "") || "g3ar4ub-cover";
  return `${safe}.webp`;
}

export async function optimizeCoverToWebp(inputPath) {
  const backends = await availableBackends();
  if (!backends.length) {
    throw new Error("Thiếu bộ mã hóa ảnh. Hãy cài ImageMagick hoặc FFmpeg và thêm executable vào PATH.");
  }

  const tempDir = await fs.mkdtemp(path.join(os.tmpdir(), "g3ai-cover-"));
  const outputPath = path.join(tempDir, "cover.webp");
  let lastError = null;

  try {
    for (const backend of backends) {
      try {
        for (const quality of QUALITY_STEPS) {
          await fs.rm(outputPath, { force: true });
          if (backend.type === "imagemagick") {
            await encodeWithImageMagick(backend.path, inputPath, outputPath, quality);
          } else {
            await encodeWithFfmpeg(backend.path, inputPath, outputPath, quality);
          }

          const stat = await fs.stat(outputPath);
          if (stat.size > 0 && stat.size <= MAX_WEBP_BYTES) {
            return {
              buffer: await fs.readFile(outputPath),
              filename: webpName(inputPath),
              mime: "image/webp",
              width: COVER_WIDTH,
              height: COVER_HEIGHT,
              bytes: stat.size,
              quality,
              encoder: backend.type,
            };
          }
        }
        lastError = new Error("Không thể giảm cover xuống dưới 100 KB mà vẫn giữ kích thước 800×400.");
      } catch (error) {
        lastError = error;
      }
    }
  } finally {
    await fs.rm(tempDir, { recursive: true, force: true });
  }

  throw new Error(lastError?.message || "Không thể tối ưu cover WebP.");
}
