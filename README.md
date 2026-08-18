# Solana DEX Reliability Index

Measured transaction failure rates for Solana DEX programs, sampled directly from
finalized blocks. Not estimates, not aggregator numbers - the count of transactions
that touched each program and the share of them that failed.

Updated weekly. Data is free to use under MIT.

## Latest measurement

Sampled `2026-08-15T07:32:49Z` - 7 finalized blocks, slots 439388181 to 439390981,
a 21 minute window containing 7,913 transactions.

| Venue | Transactions | Failed | Failure rate | Median fee (lamports) |
|---|---:|---:|---:|---:|
| Pump.fun curve | 882 | 773 | **87.6%** | 5,435 |
| Raydium AMM v4 | 28 | 7 | 25.0% | 30,491 |
| Jupiter v6 | 43 | 10 | 23.3% | 6,000 |
| Raydium CLMM | 63 | 13 | 20.6% | 5,617 |
| PumpSwap | 949 | 175 | 18.4% | 6,020 |
| Orca Whirlpool | 22 | 4 | 18.2% | 5,364 |
| Meteora DLMM | 200 | 34 | 17.0% | 5,239 |
| **Network-wide** | **7,913** | **1,050** | **13.3%** | **5,000** |

## What stands out

**The bonding curve is not like the rest of the network.** Pump.fun curve transactions
failed at 87.6% in this sample, against a network-wide rate of 13.3%. That is a
different regime, not a worse version of the same one. Curve pricing responds to every
purchase, so competing transactions invalidate each other's expected output and get
rejected on slippage. Anyone budgeting a campaign on curve-stage tokens in confirmed
swaps rather than attempts will find the two numbers are not close.

**Pooled venues cluster between 17% and 25%.** Meteora DLMM, PumpSwap and Orca
Whirlpool land within a few points of each other. The spread across pooled venues is
much narrower than the gap between any of them and the curve.

**Most transactions pay no priority fee at all.** Across 7,551 sampled transactions,
69.3% paid zero above the 5,000 lamport base fee. The median priority fee is 0, the
75th percentile is 6 lamports, and the 90th is 1,314. The distribution is not a curve
with a fat middle; it is a floor with a thin tail.

| Priority fee percentile | Lamports |
|---|---:|
| p50 | 0 |
| p75 | 6 |
| p90 | 1,314 |
| p99 | 10,068 |

Priority fee is measured as paid fee minus the 5,000 lamport base fee, over
single-signature transactions only.

## Method

1. `getSlot` with `finalized` commitment to find the current head.
2. `getBlock` over a slot range, with `transactionDetails: "accounts"`,
   `rewards: false`, `maxSupportedTransactionVersion: 0`, `commitment: "finalized"`.
   The `accounts` detail level returns the account keys each transaction touched
   plus its error status, which is everything needed and a fraction of the payload
   of full transaction data.
3. A transaction is attributed to a venue when its account keys include that venue's
   program id. A transaction routed through several programs is counted once per
   program it touched, so venue shares do not sum to 100%.
4. Failure is `meta.err !== null` - the transaction was included in a block and did
   not execute. Transactions that never landed are not in the block and cannot be
   counted here, so the real attempt-to-success ratio is worse than these figures.
5. `getRecentPerformanceSamples` for TPS, `getPriorityFeeEstimate` for the fee market.

### Program ids

| Venue | Program id |
|---|---|
| Pump.fun curve | `6EF8rrecthR5Dkzon8Nwu78hRvfCKubJ14M5uBEwF6P` |
| PumpSwap | `pAMMBay6oceH9fJKBRHGP5D4bD4sWpmSwMn52FMfXEA` |
| Raydium AMM v4 | `675kPX9MHTjS2zt1qfr1NYHuzeLXfQM9H24wFSUt1Mp8` |
| Raydium CLMM | `CAMMCzo5YL8w4VFF8KVHrK22GGUsp5VTaW7grrKgrWqK` |
| Raydium CPMM | `CPMDWBwJDtYax9qW7AyRuVC19Cc4L4Vcy4n2BHAbHkCW` |
| Meteora DLMM | `LBUZKhRxPF3XUpBCjp4YzTKgLccjZhTSDM9YuVaPwxo` |
| Meteora Pools | `Eo7WjKq67rjJQSZxS6z3YkapzY3eMj6Xy8X5EQVn5UaB` |
| Orca Whirlpool | `whirLbMiicVdio4qvUfM5KAg6Ct8VwpYzGff3uctyCc` |
| Jupiter v6 | `JUP6LkbZbjS1jKKwapdHNy74zcZ3tLUZoi5QNyVTaV4` |
| Phoenix | `PhoeNiXZ8ByJGLkxNfZRnkUfjvmuYqLR89jjFHGqdXY` |
| Lifinity v2 | `2wT8Yq49kHgDzXuPxZSaeLaH1qbmGXtEyPy64bL7aD3c` |

## Limitations

Read these before quoting the numbers.

- **Small sample per run.** Each measurement covers a handful of blocks over roughly
  20 minutes. It is a snapshot of conditions at that moment, not a monthly average.
  Low-volume venues in a given sample carry tens of transactions, so their rates move
  a lot between runs. Use the high-count rows with more confidence than the low ones.
- **Conditions vary.** Failure rates rise during contested periods and fall when the
  network is quiet. A single sample taken during congestion will read worse than the
  same venue on a calm afternoon.
- **Only landed transactions are visible.** Anything that expired before inclusion
  never reaches a block. Every figure here understates total attempt failure.
- **Multi-program routing double-counts.** A Jupiter route that settles on Raydium
  appears under both.

## Files

| Path | Contents |
|---|---|
| `data/latest.json` | Most recent measurement plus history array |
| `data/latest.csv` | Same venue table, flat CSV |
| `data/history/` | One JSON file per measurement date |
| `fetch.php` | Pulls the current measurement into this repo |

## Using the data

```bash
curl -s https://raw.githubusercontent.com/gilbert983/solana-dex-reliability-index/main/data/latest.json
```

```python
import json, urllib.request
url = "https://raw.githubusercontent.com/gilbert983/solana-dex-reliability-index/main/data/latest.json"
d = json.load(urllib.request.urlopen(url))
for v in d["latest"]["venues"]:
    print(f'{v["venue"]:<18} {v["failure_rate"]:>5}%  n={v["transactions"]}')
```

## Source

Measurements are produced by the on-chain sampler behind
[solanavolumebotpro.com/solana-volume-data](https://www.solanavolumebotpro.com/solana-volume-data/),
which runs the same method continuously and publishes the live figures. The
methodology write-up is at
[solanavolumebotpro.com/how-we-measure](https://www.solanavolumebotpro.com/how-we-measure/).

If you cite these numbers, please include the measurement date - they move.

## Licence

MIT. Attribution appreciated, not required.
